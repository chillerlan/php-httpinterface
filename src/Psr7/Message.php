<?php
/**
 * Class Message
 *
 * @created      11.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 *
 * @phan-file-suppress PhanParamSignatureMismatch
 */

namespace chillerlan\HTTP\Psr7;

use chillerlan\HTTP\Psr17\FactoryHelpers;
use chillerlan\HTTP\Psr17\StreamFactory;
use chillerlan\HTTP\Utils\Header;
use Psr\Http\Message\{MessageInterface, StreamInterface};

use function array_map, array_merge, implode, is_array, strtolower, trim;

abstract class Message implements MessageInterface{

	protected array $headers = [];
	/** @var string[] */
	protected array $headerNames = [];

	protected string $version;

	protected StreamInterface $body;

	protected StreamFactory $streamFactory;

	/**
	 * Message constructor.
	 *
	 * @param array|null                                             $headers
	 * @param null|string|resource|\Psr\Http\Message\StreamInterface $body
	 * @param string|null                                            $version
	 */
	public function __construct(array $headers = null, $body = null, string $version = null){
		$this->setHeaders(Header::normalize($headers ?? []));

		$this->version       = $version ?? '1.1';
		$this->streamFactory = new StreamFactory;

		if($body instanceof StreamInterface){
			$this->body = $body;
		}
		elseif($body !== null && $body !== ''){
			$this->body = FactoryHelpers::create_stream_from_input($body);
		}
		else{
			$this->body = $this->streamFactory->createStream();
		}

	}

	/**
	 * @inheritDoc
	 */
	public function getProtocolVersion():string{
		return $this->version;
	}

	/**
	 * @inheritDoc
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
	 * @inheritDoc
	 */
	public function getHeaders():array{
		return $this->headers;
	}

	/**
	 * @inheritDoc
	 */
	public function hasHeader($name):bool{
		return isset($this->headerNames[strtolower($name)]);
	}

	/**
	 * @inheritDoc
	 */
	public function getHeader($name):array{

		if(!$this->hasHeader($name)){
			return [];
		}

		return $this->headers[$this->headerNames[strtolower($name)]];
	}

	/**
	 * @inheritDoc
	 */
	public function getHeaderLine($name):string{
		return implode(', ', $this->getHeader($name));
	}

	/**
	 * @inheritDoc
	 */
	public function withHeader($name, $value):MessageInterface{

		if(!is_array($value)){
			$value = [$value];
		}

		$value      = $this->trimHeaderValues($value);
		$normalized = strtolower($name);
		$clone      = clone $this;

		if(isset($clone->headerNames[$normalized])){
			unset($clone->headers[$clone->headerNames[$normalized]]);
		}

		$clone->headerNames[$normalized] = $name;
		$clone->headers[$name]           = $value;

		return $clone;
	}

	/**
	 * @inheritDoc
	 */
	public function withAddedHeader($name, $value):MessageInterface{

		if(!is_array($value)){
			$value = [$value];
		}

		$value      = $this->trimHeaderValues($value);
		$normalized = strtolower($name);
		$clone      = clone $this;

		/** @noinspection DuplicatedCode */
		if(isset($clone->headerNames[$normalized])){
			$name                  = $this->headerNames[$normalized];
			$clone->headers[$name] = array_merge($this->headers[$name], $value);
		}
		else{
			$clone->headerNames[$normalized] = $name;
			$clone->headers[$name]           = $value;
		}

		return $clone;
	}

	/**
	 * @inheritDoc
	 */
	public function withoutHeader($name):MessageInterface{
		$normalized = strtolower($name);

		if(!isset($this->headerNames[$normalized])){
			return $this;
		}

		$name  = $this->headerNames[$normalized];
		$clone = clone $this;

		unset($clone->headers[$name], $clone->headerNames[$normalized]);

		return $clone;
	}

	/**
	 * @inheritDoc
	 */
	public function getBody():StreamInterface{
		return $this->body;
	}

	/**
	 * @inheritDoc
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

		foreach($headers as $name => $value){

			if(!is_array($value)){
				$value = [$value];
			}

			$value      = $this->trimHeaderValues($value);
			$normalized = strtolower($name);

			/** @noinspection DuplicatedCode */
			if(isset($this->headerNames[$normalized])){
				$name                 = $this->headerNames[$normalized];
				/** @phan-suppress-next-line PhanTypeInvalidDimOffset */
				$this->headers[$name] = array_merge($this->headers[$name], $value);
			}
			else{
				$this->headerNames[$normalized] = $name;
				$this->headers[$name]           = $value;
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
		return array_map(fn(string $value):string => trim($value, " \t"), $values);
	}

}
