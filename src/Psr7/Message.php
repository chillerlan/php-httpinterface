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

use function array_column, array_combine, array_merge, implode, is_array, strtolower;

class Message implements MessageInterface{

	protected array           $headers = [];
	protected string          $version = '1.1';
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
		return array_combine(array_column($this->headers, 'name'), array_column($this->headers, 'value'));
	}

	/**
	 * @inheritDoc
	 */
	public function hasHeader(string $name):bool{
		return isset($this->headers[strtolower($name)]);
	}

	/**
	 * @inheritDoc
	 */
	public function getHeader(string $name):array{

		if(!$this->hasHeader($name)){
			return [];
		}

		return $this->headers[strtolower($name)]['value'];
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

		$clone = clone $this;

		$clone->headers[strtolower($name)] = ['name' => $name, 'value' => HeaderUtil::trimValues($value)];

		return $clone;
	}

	/**
	 * @inheritDoc
	 */
	public function withAddedHeader(string $name, $value):static{

		if(!is_array($value)){
			$value = [$value];
		}

		$value  = HeaderUtil::trimValues($value);
		$lcName = strtolower($name);

		if(isset($this->headers[$lcName])){
			$name = $this->headers[$lcName]['name'];
		}

		$clone = clone $this;

		$clone->headers[$lcName] = ['name' => $name, 'value' => array_merge(($this->headers[$lcName]['value'] ?? []), $value)];

		return $clone;
	}

	/**
	 * @inheritDoc
	 */
	public function withoutHeader(string $name):static{
		$lcName = strtolower($name);

		if(!isset($this->headers[$lcName])){
			return $this;
		}

		$clone = clone $this;

		unset($clone->headers[$lcName]);

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
