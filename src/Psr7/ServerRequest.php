<?php
/**
 * Class ServerRequest
 *
 * @filesource   ServerRequest.php
 * @created      11.08.2018
 * @package      chillerlan\HTTP\Psr7
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

final class ServerRequest extends Request implements ServerRequestInterface{

	/**
	 * @var array
	 */
	private $serverParams;

	/**
	 * @var array
	 */
	private $cookieParams = [];

	/**
	 * @var array
	 */
	private $queryParams = [];

	/**
	 * @var null|array|object
	 */
	private $parsedBody;

	/**
	 * @var array
	 */
	private $attributes = [];

	/**
	 * @var array
	 */
	private $uploadedFiles = [];

	/**
	 * ServerRequest constructor.
	 *
	 * @param string                                                 $method
	 * @param                                                        $uri
	 * @param array|null                                             $headers
	 * @param null|string|resource|\Psr\Http\Message\StreamInterface $body
	 * @param string|null                                            $version
	 * @param array|null                                             $serverParams
	 */
	public function __construct(string $method, $uri, array $headers = null, $body = null, string $version = null, array $serverParams = null){
		parent::__construct($method, $uri, $headers, $body, $version);

		$this->serverParams = $serverParams ?? [];
	}

	/**
	 * @inheritdoc
	 */
	public function getServerParams():array{
		return $this->serverParams;
	}

	/**
	 * @inheritdoc
	 */
	public function getCookieParams():array{
		return $this->cookieParams;
	}

	/**
	 * @inheritdoc
	 */
	public function withCookieParams(array $cookies):ServerRequestInterface{
		$clone               = clone $this;
		$clone->cookieParams = $cookies;

		return $clone;
	}

	/**
	 * @inheritdoc
	 */
	public function getQueryParams():array{
		return $this->queryParams;
	}

	/**
	 * @inheritdoc
	 */
	public function withQueryParams(array $query):ServerRequestInterface{
		$clone              = clone $this;
		$clone->queryParams = $query;

		return $clone;
	}

	/**
	 * @inheritdoc
	 */
	public function getUploadedFiles():array{
		return $this->uploadedFiles;
	}

	/**
	 * @inheritdoc
	 */
	public function withUploadedFiles(array $uploadedFiles):ServerRequestInterface{
		$clone                = clone $this;
		$clone->uploadedFiles = $uploadedFiles;

		return $clone;
	}

	/**
	 * @inheritdoc
	 */
	public function getParsedBody(){
		return $this->parsedBody;
	}

	/**
	 * @inheritdoc
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
	 * @inheritdoc
	 */
	public function getAttributes():array{
		return $this->attributes;
	}

	/**
	 * @inheritdoc
	 */
	public function getAttribute($name, $default = null){

		if(array_key_exists($name, $this->attributes)){
			return $this->attributes[$name];
		}

		return $default;
	}

	/**
	 * @inheritdoc
	 */
	public function withAttribute($name, $value):ServerRequestInterface{
		$clone                    = clone $this;
		$clone->attributes[$name] = $value;

		return $clone;
	}

	/**
	 * @inheritdoc
	 */
	public function withoutAttribute($name):ServerRequestInterface{

		if(array_key_exists($name, $this->attributes)){
			$clone = clone $this;
			unset($clone->attributes[$name]);

			return $clone;
		}

		return $this;
	}


}
