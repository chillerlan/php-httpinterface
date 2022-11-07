<?php
/**
 * Class ServerRequest
 *
 * @created      11.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\{ServerRequestInterface, UriInterface};

use function array_key_exists, is_array, is_object;

class ServerRequest extends Request implements ServerRequestInterface{

	protected array $serverParams;

	protected array $cookieParams = [];

	protected array $queryParams = [];

	protected array $attributes = [];

	protected array $uploadedFiles = [];

	protected array|object|null $parsedBody = null;

	/**
	 * ServerRequest constructor.
	 */
	public function __construct(string $method, UriInterface|string $uri, array $serverParams = null){
		parent::__construct($method, $uri);

		$this->serverParams = $serverParams ?? [];
	}

	/**
	 * @inheritDoc
	 */
	public function getServerParams():array{
		return $this->serverParams;
	}

	/**
	 * @inheritDoc
	 */
	public function getCookieParams():array{
		return $this->cookieParams;
	}

	/**
	 * @inheritDoc
	 */
	public function withCookieParams(array $cookies):ServerRequestInterface{
		$clone               = clone $this;
		$clone->cookieParams = $cookies;

		return $clone;
	}

	/**
	 * @inheritDoc
	 */
	public function getQueryParams():array{
		return $this->queryParams;
	}

	/**
	 * @inheritDoc
	 */
	public function withQueryParams(array $query):ServerRequestInterface{
		$clone              = clone $this;
		$clone->queryParams = $query;

		return $clone;
	}

	/**
	 * @inheritDoc
	 */
	public function getUploadedFiles():array{
		return $this->uploadedFiles;
	}

	/**
	 * @inheritDoc
	 */
	public function withUploadedFiles(array $uploadedFiles):ServerRequestInterface{
		$clone                = clone $this;
		$clone->uploadedFiles = $uploadedFiles;

		return $clone;
	}

	/**
	 * @inheritDoc
	 */
	public function getParsedBody():array|object|null{
		return $this->parsedBody;
	}

	/**
	 * @inheritDoc
	 */
	public function withParsedBody($data):ServerRequestInterface{

		if($data !== null && !is_object($data) && !is_array($data)){
			throw new InvalidArgumentException('parsed body value must be an array, object or null');
		}

		$clone             = clone $this;
		$clone->parsedBody = $data;

		return $clone;
	}

	/**
	 * @inheritDoc
	 */
	public function getAttributes():array{
		return $this->attributes;
	}

	/**
	 * @inheritDoc
	 */
	public function getAttribute($name, $default = null):mixed{

		if(!array_key_exists($name, $this->attributes)){
			return $default;
		}

		return $this->attributes[$name];
	}

	/**
	 * @inheritDoc
	 */
	public function withAttribute($name, $value):ServerRequestInterface{
		$clone                    = clone $this;
		$clone->attributes[$name] = $value;

		return $clone;
	}

	/**
	 * @inheritDoc
	 */
	public function withoutAttribute($name):ServerRequestInterface{

		if(!array_key_exists($name, $this->attributes)){
			return $this;
		}

		$clone = clone $this;
		unset($clone->attributes[$name]);

		return $clone;
	}

}
