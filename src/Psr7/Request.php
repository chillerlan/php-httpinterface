<?php
/**
 * Class Request
 *
 * @filesource   Request.php
 * @created      11.08.2018
 * @package      chillerlan\HTTP\Psr7
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use Fig\Http\Message\RequestMethodInterface;
use InvalidArgumentException;
use Psr\Http\Message\{RequestInterface, UriInterface};

class Request extends Message implements RequestInterface, RequestMethodInterface{

	/**
	 * @var string
	 */
	protected $method;

	/**
	 * @var \Psr\Http\Message\UriInterface
	 */
	protected $uri;

	/**
	 * @var string
	 */
	protected $requestTarget;

	/**
	 * Request constructor.
	 *
	 * @param string                                                 $method
	 * @param string|\Psr\Http\Message\UriInterface                  $uri
	 * @param array|null                                             $headers
	 * @param null|string|resource|\Psr\Http\Message\StreamInterface $body
	 * @param string|null                                            $version
	 */
	public function __construct(string $method, $uri, array $headers = null, $body = null, string $version = null){
		parent::__construct($headers, $body, $version);

		$this->method = strtoupper($method);
		$this->uri    = !$uri instanceof UriInterface
			? new Uri($uri)
			: $uri;

		if(!$this->hasHeader('Host')){
			$this->updateHostFromUri();
		}

	}

	/**
	 * @inheritdoc
	 */
	public function getRequestTarget():string{

		if($this->requestTarget !== null){
			return $this->requestTarget;
		}

		$target = $this->uri->getPath();

		if($target == ''){
			$target = '/';
		}

		if($this->uri->getQuery() !== ''){
			$target .= '?'.$this->uri->getQuery();
		}

		return $target;
	}

	/**
	 * @inheritdoc
	 */
	public function withRequestTarget($requestTarget):RequestInterface{

		if(preg_match('#\s#', $requestTarget)){
			throw new InvalidArgumentException('Invalid request target provided; cannot contain whitespace');
		}

		$clone                = clone $this;
		$clone->requestTarget = $requestTarget;

		return $clone;
	}

	/**
	 * @inheritdoc
	 */
	public function getMethod(){
		return $this->method;
	}

	/**
	 * @inheritdoc
	 */
	public function withMethod($method):RequestInterface{

		if(!is_string($method)){
			throw new InvalidArgumentException('Method must be a string');
		}

		$clone         = clone $this;
		$clone->method = strtoupper($method);

		return $clone;
	}

	/**
	 * @inheritdoc
	 */
	public function getUri():UriInterface{
		return $this->uri;
	}

	/**
	 * @inheritdoc
	 */
	public function withUri(UriInterface $uri, $preserveHost = false):RequestInterface{

		if($uri === $this->uri){
			return $this;
		}

		$new      = clone $this;
		$new->uri = $uri;

		if(!$preserveHost){
			$new->updateHostFromUri();
		}

		return $new;
	}

	/**
	 * @return void
	 */
	protected function updateHostFromUri():void{
		$host = $this->uri->getHost();

		if($host === ''){
			return;
		}

		if(($port = $this->uri->getPort()) !== null){
			$host .= ':'.$port;
		}

		if(isset($this->headerNames['host'])){
			$header = $this->headerNames['host'];
		}
		else{
			$header                    = 'Host';
			$this->headerNames['host'] = 'Host';
		}
		// Ensure Host is the first header.
		// See: http://tools.ietf.org/html/rfc7230#section-5.4
		$this->headers = [$header => [$host]] + $this->headers;
	}

}
