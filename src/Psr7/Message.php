<?php
/**
 * Class Message
 *
 * @created      11.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use chillerlan\HTTP\Common\FactoryHelpers;
use chillerlan\HTTP\Utils\HeaderUtil;
use Psr\Http\Message\{MessageInterface, StreamInterface};

use function array_merge, implode, is_array, strtolower;

class Message implements MessageInterface{

	protected array $headers = [];
	/** @var string[] */
	protected array $headerNames = [];

	protected string $version = '1.1';

	protected StreamInterface $body;

	/**
	 * Message constructor.
	 */
	public function __construct(){
		$this->body = FactoryHelpers::createStream();
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
	public function withProtocolVersion(string $version):static{

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
	public function hasHeader(string $name):bool{
		return isset($this->headerNames[strtolower($name)]);
	}

	/**
	 * @inheritDoc
	 */
	public function getHeader(string $name):array{

		if(!$this->hasHeader($name)){
			return [];
		}

		return $this->headers[$this->headerNames[strtolower($name)]];
	}

	/**
	 * @inheritDoc
	 */
	public function getHeaderLine(string $name):string{
		return implode(', ', $this->getHeader($name));
	}

	/**
	 * @inheritDoc
	 */
	public function withHeader(string $name, $value):static{

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
	public function withAddedHeader(string $name, $value):static{

		if(!is_array($value)){
			$value = [$value];
		}

		$value      = HeaderUtil::trimValues($value);
		$normalized = strtolower($name);
		$clone      = clone $this;

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
	public function withoutHeader(string $name):static{
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
	public function withBody(StreamInterface $body):static{

		if($body === $this->body){
			return $this;
		}

		$clone       = clone $this;
		$clone->body = $body;

		return $clone;
	}

}
