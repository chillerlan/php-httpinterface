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
	 * @param \chillerlan\TinyCurl\Request|null     $http
	 */
	public function __construct(ContainerInterface $options, Request $http = null){
		parent::__construct($options);

		if($http instanceof Request){
			$this->setClient($http);
		}
	}

	/** @inheritdoc */
	public function setClient(Request $http):HTTPClientInterface{
		$this->http = $http;

		return $this;
	}

	/** @inheritdoc */
	public function request(string $url, array $params = null, string $method = null, $body = null, array $headers = null):HTTPResponseInterface{

		try{

			$parsedURL = parse_url($url);

			if(!isset($parsedURL['host']) || !in_array($parsedURL['scheme'], ['http', 'https'], true)){
				trigger_error('invalid URL');
			}

			$url = new URL(
				explode('?', $url)[0],
				$params ?? [],
				$method ?? 'POST',
				$body,
				$headers ?? []
			);

			$response = $this->http->fetch($url);

			return new HTTPResponse([
				'url'     => $url->__toString(),
				'headers' => $response->headers,
				'body'    => $response->body->content,
			]);

		}
		catch(\Exception $e){
			throw new HTTPClientException('fetch error: '.$e->getMessage());
		}

	}

}
