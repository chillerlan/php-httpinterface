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
	protected function getResponse():HTTPResponseInterface{

		$url = new URL(
			explode('?', $this->requestURL)[0],
			$this->requestParams,
			$this->requestMethod,
			$this->requestBody,
			$this->requestHeaders
		);

		$response = $this->http->fetch($url);

		return new HTTPResponse([
			'url'     => $url->__toString(),
			'headers' => $response->headers,
			'body'    => $response->body->content,
		]);
	}

}
