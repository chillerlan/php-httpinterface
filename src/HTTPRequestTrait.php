<?php
/**
 * Trait HTTPRequestTrait
 *
 * @filesource   HTTPRequestTrait.php
 * @created      02.09.2018
 * @package      chillerlan\HTTP
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

use Psr\Http\Message\ResponseInterface;

/**
 * @property \Psr\Http\Message\RequestFactoryInterface $requestFactory
 * @property \Psr\Http\Message\StreamFactoryInterface  $streamFactory
 * @method   sendRequest(\Psr\Http\Message\RequestInterface $request):\Psr\Http\Message\ResponseInterface
 */
trait HTTPRequestTrait{

	/**
	 * @todo: files, content-type
	 *
	 * @param string      $uri
	 * @param string|null $method
	 * @param array|null  $query
	 * @param mixed|null  $body
	 * @param array|null  $headers
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function request(string $uri, string $method = null, array $query = null, $body = null, array $headers = null):ResponseInterface{
		$method    = strtoupper($method ?? 'GET');
		$headers   = Psr7\normalize_request_headers($headers);
		$request   = $this->requestFactory->createRequest($method, Psr7\merge_query($uri, $query ?? []));

		if(in_array($method, ['DELETE', 'PATCH', 'POST', 'PUT'], true) && $body !== null){

			if(is_array($body) || is_object($body)){

				if(!isset($headers['Content-type'])){
					$headers['Content-type'] = 'application/x-www-form-urlencoded';
				}

				if($headers['Content-type'] === 'application/x-www-form-urlencoded'){
					$body = http_build_query($body, '', '&', PHP_QUERY_RFC1738);
				}
				elseif($headers['Content-type'] === 'application/json'){
					$body = json_encode($body);
				}

			}

			$request = $request->withBody($this->streamFactory->createStream((string)$body));
		}

		foreach($headers as $header => $value){
			$request = $request->withAddedHeader($header, $value);
		}

		return $this->sendRequest($request);
	}

}
