<?php
/**
 * Class TinyCurlClient
 *
 * @filesource   TinyCurlClient.php
 * @created      09.07.2017
 * @package      chillerlan\HTTP
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

use chillerlan\TinyCurl\{Request, URL};
use chillerlan\Traits\ContainerInterface;

/**
 * @property \chillerlan\TinyCurl\Request $http
 */
class TinyCurlClient extends HTTPClientAbstract{

	/**
	 * TinyCurlClient constructor.
	 *
	 * @param \chillerlan\Traits\ContainerInterface $options
	 * @param \chillerlan\TinyCurl\Request          $http
	 */
	public function __construct(ContainerInterface $options, Request $http = null){
		parent::__construct($options);

		$this->setClient($http);
	}

	/** @inheritdoc */
	public function setClient(Request $http):HTTPClientInterface{
		$this->http = $http;

		return $this;
	}

	/**
	 * @param string $url
	 * @param array  $params
	 * @param string $method
	 * @param mixed  $body
	 * @param array  $headers
	 *
	 * @return \chillerlan\HTTP\HTTPResponse
	 * @throws \chillerlan\HTTP\HTTPClientException
	 */
	public function request(string $url, array $params = null, string $method = null, $body = null, array $headers = null):HTTPResponse{

		try{

			$parsedURL = parse_url($url);

			if(!isset($parsedURL['host']) || $parsedURL['scheme'] !== 'https'){
				trigger_error('invalid URL');
			}

			$response = $this->http->fetch(new URL(
				explode('?', $url)[0],
				$params ?? [],
				$method ?? 'POST',
				$body,
				$headers ?? []
			));

			return new HTTPResponse([
				'headers' => $response->headers,
				'body'    => $response->body->content,
			]);

		}
		catch(\Exception $e){
			throw new HTTPClientException('fetch error: '.$e->getMessage());
		}

	}

}
