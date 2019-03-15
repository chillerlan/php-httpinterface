<?php
/**
 * Class Message
 *
 * @filesource   Message.php
 * @created      11.08.2018
 * @package      chillerlan\HTTP\Psr7
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use chillerlan\HTTP\{Psr17, Psr17\StreamFactory};
use Psr\Http\Message\{MessageInterface, StreamInterface};

abstract class Message implements MessageInterface{

	/**
	 * @var array
	 */
	protected $headers = [];

	/**
	 * @var array
	 */
	protected $headerNames = [];

	/**
	 * @var string
	 */
	protected $version;

	/**
	 * @var \Psr\Http\Message\StreamInterface
	 */
	protected $body;

	/**
	 * @var \chillerlan\HTTP\Psr17\StreamFactory
	 */
	protected $streamFactory;

	/**
	 * Message constructor.
	 *
	 * @param array|null                                             $headers
	 * @param null|string|resource|\Psr\Http\Message\StreamInterface $body
	 * @param string|null                                            $version
	 */
	public function __construct(array $headers = null, $body = null, string $version = null){
		$this->setHeaders($headers ?? []);

		$this->version       = $version ?? '1.1';
		$this->streamFactory = new StreamFactory;

		$this->body = $body !== null && $body !== ''
			? Psr17\create_stream_from_input($body)
			: $this->streamFactory->createStream();
	}

	/**
	 * @inheritdoc
	 */
	public function getProtocolVersion():string{
		return $this->version;
	}

	/**
	 * @inheritdoc
	 */
	public function withProtocolVersion($version):MessageInterface{

		if($this->version === $version){
			return $this;
		}

		$clone          = clone $this;
		$clone->version = $version;

		return $clone;
	}

	/**
	 * @inheritdoc
	 */
	public function getHeaders():array{
		return $this->headers;
	}

	/**
	 * @inheritdoc
	 */
	public function hasHeader($name):bool{
		return isset($this->headerNames[\strtolower($name)]);
	}

	/**
	 * @inheritdoc
	 */
	public function getHeader($name):array{

		if(!$this->hasHeader($name)){
			return [];
		}

		return $this->headers[$this->headerNames[\strtolower($name)]];
	}

	/**
	 * @inheritdoc
	 */
	public function getHeaderLine($name):string{
		return \implode(', ', $this->getHeader($name));
	}

	/**
	 * @inheritdoc
	 */
	public function withHeader($name, $value):MessageInterface{

		if(!\is_array($value)){
			$value = [$value];
		}

		$value      = $this->trimHeaderValues($value);
		$normalized = \strtolower($name);
		$clone      = clone $this;

		if(isset($clone->headerNames[$normalized])){
			unset($clone->headers[$clone->headerNames[$normalized]]);
		}

		$clone->headerNames[$normalized] = $name;
		$clone->headers[$name]           = $value;

		return $clone;
	}

	/**
	 * @inheritdoc
	 */
	public function withAddedHeader($name, $value):MessageInterface{

		if(!\is_array($value)){
			$value = [$value];
		}

		$value      = $this->trimHeaderValues($value);
		$normalized = \strtolower($name);
		$clone      = clone $this;

		if(isset($clone->headerNames[$normalized])){
			$name                  = $this->headerNames[$normalized];
			$clone->headers[$name] = \array_merge($this->headers[$name], $value);
		}
		else{
			$clone->headerNames[$normalized] = $name;
			$clone->headers[$name]           = $value;
		}

		return $clone;
	}

	/**
	 * @inheritdoc
	 */
	public function withoutHeader($name):MessageInterface{
		$normalized = \strtolower($name);

		if(!isset($this->headerNames[$normalized])){
			return $this;
		}

		$name  = $this->headerNames[$normalized];
		$clone = clone $this;

		unset($clone->headers[$name], $clone->headerNames[$normalized]);

		return $clone;
	}

	/**
	 * @inheritdoc
	 */
	public function getBody():StreamInterface{
		return $this->body;
	}

	/**
	 * @inheritdoc
	 */
	public function withBody(StreamInterface $body):MessageInterface{

		if($body === $this->body){
			return $this;
		}

		$clone       = clone $this;
		$clone->body = $body;

		return $clone;
	}

	/**
	 * @param array $headers
	 */
	protected function setHeaders(array $headers):void{
		$this->headers     = [];
		$this->headerNames = [];

		foreach($headers as $header => $value){

			if(!\is_array($value)){
				$value = [$value];
			}

			$value      = $this->trimHeaderValues($value);
			$normalized = \strtolower($header);

			if(isset($this->headerNames[$normalized])){
				$header                 = $this->headerNames[$normalized];
				$this->headers[$header] = \array_merge($this->headers[$header], $value);
			}
			else{
				$this->headerNames[$normalized] = $header;
				$this->headers[$header]         = $value;
			}

		}
	}

	/**
	 * Trims whitespace from the header values.
	 *
	 * Spaces and tabs ought to be excluded by parsers when extracting the field value from a header field.
	 *
	 * header-field = field-name ":" OWS field-value OWS
	 * OWS          = *( SP / HTAB )
	 *
	 * @param string[] $values Header values
	 *
	 * @return string[] Trimmed header values
	 *
	 * @see https://tools.ietf.org/html/rfc7230#section-3.2.4
	 */
	protected function trimHeaderValues(array $values):array{
		return \array_map(function($value){
			return \trim($value, " \t");
		}, $values);
	}

}
