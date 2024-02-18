<?php
/**
 * Class ServerRequest
 *
 * @created      11.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTP\Psr7;

use Psr\Http\Message\{ServerRequestInterface, UriInterface};
use InvalidArgumentException;
use function array_key_exists, is_array, is_object;

/**
 * Implements a server-side incoming HTTP request
 */
class ServerRequest extends Request implements ServerRequestInterface{

	protected array             $serverParams;
	protected array             $cookieParams  = [];
	protected array             $queryParams   = [];
	protected array             $attributes    = [];
	protected array             $uploadedFiles = [];
	protected array|object|null $parsedBody    = null;

	/**
	 * ServerRequest constructor.
	 */
	public function __construct(string $method, UriInterface|string $uri, array|null $serverParams = null){
		parent::__construct($method, $uri);

		$this->serverParams = ($serverParams ?? []);
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
	public function withCookieParams(array $cookies):static{
		$this->cookieParams = $cookies;

		return $this;
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
	public function withQueryParams(array $query):static{
		$this->queryParams = $query;

		return $this;
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
	public function withUploadedFiles(array $uploadedFiles):static{
		$this->uploadedFiles = $uploadedFiles;

		return $this;
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
	public function withParsedBody(mixed $data):static{

		if($data !== null && !is_object($data) && !is_array($data)){
			throw new InvalidArgumentException('parsed body value must be an array, object or null');
		}

		$this->parsedBody = $data;

		return $this;
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
	public function getAttribute(string $name, mixed $default = null):mixed{

		if(!array_key_exists($name, $this->attributes)){
			return $default;
		}

		return $this->attributes[$name];
	}

	/**
	 * @inheritDoc
	 */
	public function withAttribute(string $name, mixed $value):static{
		$this->attributes[$name] = $value;

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function withoutAttribute(string $name):static{

		if(array_key_exists($name, $this->attributes)){
			unset($this->attributes[$name]);
		}

		return $this;
	}

}
