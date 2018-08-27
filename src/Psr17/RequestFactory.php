<?php
/**
 * Class RequestFactory
 *
 * @filesource   RequestFactory.php
 * @created      27.08.2018
 * @package      chillerlan\HTTP\Psr17
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr17;

use chillerlan\HTTP\Psr7\Request;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\{RequestFactoryInterface, RequestInterface};

class RequestFactory implements RequestFactoryInterface, RequestMethodInterface{

	/**
	 * Create a new request.
	 *
	 * @param string                                $method The HTTP method associated with the request.
	 * @param \Psr\Http\Message\UriInterface|string $uri    The URI associated with the request. If
	 *                                                      the value is a string, the factory MUST create a
	 *                                                      UriInterface instance based on it.
	 *
	 * @return \Psr\Http\Message\RequestInterface|\chillerlan\HTTP\Psr7\Request
	 */
	public function createRequest(string $method, $uri):RequestInterface{
		return new Request($method, $uri);
	}

	/**
	 * @param array $headers
	 *
	 * @return array
	 */
	public function normalizeRequestHeaders(array $headers):array {
		$normalized_headers = [];

		foreach($headers as $key => $val){

			if(is_numeric($key)){
				$header = explode(':', $val, 2);

				if(count($header) !== 2){
					continue;
				}

				$key = $header[0];
				$val = $header[1];
			}

			$key = ucfirst(strtolower($key));

			$normalized_headers[$key] = trim($key).': '.trim($val);
		}

		return $normalized_headers;
	}

}
