<?php
/**
 * Class Message
 *
 * @created      11.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTP\Psr7;

use chillerlan\HTTP\Common\FactoryUtils;
use chillerlan\HTTP\Utils\HeaderUtil;
use Psr\Http\Message\{MessageInterface, StreamInterface};
use InvalidArgumentException;
use function array_column, array_combine, array_merge, implode, is_array, is_scalar, strtolower;

/**
 * Implements a HTTP message
 *
 * @see https://datatracker.ietf.org/doc/html/rfc7230
 * @see https://datatracker.ietf.org/doc/html/rfc7231
 * @see https://datatracker.ietf.org/doc/html/rfc9110
 * @see https://datatracker.ietf.org/doc/html/rfc9112
 */
class Message implements MessageInterface{

	protected StreamInterface $body;
	protected array           $headers = [];
	protected string          $version = '1.1';

	/**
	 * Message constructor.
	 */
	public function __construct(){
		$this->body = FactoryUtils::createStream();
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
		$this->version = $version;

		return $this;
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
	 * https://github.com/advisories/GHSA-wxmh-65f7-jcvw
	 * https://github.com/slimphp/Slim-Psr7/security/advisories/GHSA-q2qj-628g-vhfw
	 * https://github.com/advisories/GHSA-xv3h-4844-9h36
	 */
	public function withHeader(string $name, mixed $value):static{
		$this->headers[strtolower($name)] = ['name' => $name, 'value' => HeaderUtil::trimValues($this->checkValue($value))];

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function withAddedHeader(string $name, mixed $value):static{
		/** @var array $value */
		$value  = HeaderUtil::trimValues($this->checkValue($value));
		$lcName = strtolower($name);

		if(isset($this->headers[$lcName])){
			$name = $this->headers[$lcName]['name'];
		}

		/** @phan-suppress-next-line PhanTypeMismatchArgumentInternal */
		$this->headers[$lcName] = ['name' => $name, 'value' => array_merge(($this->headers[$lcName]['value'] ?? []), $value)];

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function withoutHeader(string $name):static{
		$lcName = strtolower($name);

		if(!isset($this->headers[$lcName])){
			return $this;
		}

		unset($this->headers[$lcName]);

		return $this;
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
		$this->body = $body;

		return $this;
	}

	/**
	 * @param mixed $value
	 *
	 * @return string[]
	 */
	protected function checkValue(mixed $value):array{

		if(!is_array($value)){

			if(!is_scalar($value)){
				throw new InvalidArgumentException('$value is expected to be scalar');
			}

			$value = [$value];
		}

		return $value;
	}

}
