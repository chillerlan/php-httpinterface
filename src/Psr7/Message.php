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

use chillerlan\HTTP\Utils\HeaderUtil;
use Psr\Http\Message\{MessageInterface, StreamInterface};

use function array_merge, fopen, implode, is_array, strtolower;

abstract class Message implements MessageInterface{

	protected array $headers = [];
	/** @var string[] */
	protected array $headerNames = [];

	protected string $version = '1.1';

	protected StreamInterface $body;

	/**
	 * Message constructor.
	 */
	public function __construct(){
		$this->body = new Stream(fopen('php://temp', 'r+'));
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

		$value      = HeaderUtil::trimValues($value);
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

		$value      = HeaderUtil::trimValues($value);
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

}
