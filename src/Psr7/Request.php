<?php
/**
 * Class Request
 *
 * @created      11.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use Fig\Http\Message\RequestMethodInterface;
use InvalidArgumentException;
use Psr\Http\Message\{RequestInterface, UriInterface};

use function preg_match, strtoupper, trim;

class Request extends Message implements RequestInterface, RequestMethodInterface{

	protected UriInterface $uri;
	protected string       $method;
	protected ?string      $requestTarget = null;

	/**
	 * Request constructor.
	 */
	public function __construct(string $method, UriInterface|string $uri){
		parent::__construct();

		$this->method = strtoupper(trim($method));

		if($method === ''){
			throw new InvalidArgumentException('HTTP method must not be empty');
		}

		$this->uri = $uri instanceof UriInterface ? $uri : new Uri($uri);

		$this->updateHostFromUri();
	}

	/**
	 * @inheritDoc
	 */
	public function getRequestTarget():string{

		if($this->requestTarget !== null){
			return $this->requestTarget;
		}

		$target = $this->uri->getPath();
		$query  = $this->uri->getQuery();

		if($target === ''){
			$target = '/';
		}

		if($query !== ''){
			$target .= '?'.$query;
		}

		return $target;
	}

	/**
	 * @inheritDoc
	 */
	public function withRequestTarget(string $requestTarget):static{

		if(preg_match('#\s#', $requestTarget)){
			throw new InvalidArgumentException('Invalid request target provided; cannot contain whitespace');
		}

		$clone                = clone $this;
		$clone->requestTarget = $requestTarget;

		return $clone;
	}

	/**
	 * @inheritDoc
	 */
	public function getMethod():string{
		return $this->method;
	}

	/**
	 * @inheritDoc
	 */
	public function withMethod(string $method):static{
		$method = strtoupper(trim($method));

		if($method === ''){
			throw new InvalidArgumentException('HTTP method must not be empty');
		}

		$clone         = clone $this;
		$clone->method = $method;

		return $clone;
	}

	/**
	 * @inheritDoc
	 */
	public function getUri():UriInterface{
		return $this->uri;
	}

	/**
	 * @inheritDoc
	 */
	public function withUri(UriInterface $uri, bool $preserveHost = false):static{

		if($uri === $this->uri){
			return $this;
		}

		$clone      = clone $this;
		$clone->uri = $uri;

		if(!$preserveHost){
			$clone->updateHostFromUri();
		}

		return $clone;
	}

	/**
	 *
	 */
	protected function updateHostFromUri():void{
		$host = $this->uri->getHost();

		if($host === ''){
			return;
		}

		if(($port = $this->uri->getPort()) !== null){
			$host .= ':'.$port;
		}

		// Ensure Host is the first header.
		// See: http://tools.ietf.org/html/rfc7230#section-5.4
		$this->headers = ['host' => ['name' => 'Host', 'value' => [$host]]] + $this->headers;
	}

}
