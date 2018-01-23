<?php
/**
 * Class GuzzleClient
 *
 * @filesource   GuzzleClient.php
 * @created      23.10.2017
 * @package      chillerlan\HTTP
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

use chillerlan\Traits\ContainerInterface;
use GuzzleHttp\Client;
use Psr\Http\Message\StreamInterface;

/**
 * @property \GuzzleHttp\Client $http
 */
class GuzzleClient extends HTTPClientAbstract{

	/**
	 * GuzzleClient constructor.
	 *
	 * @param \chillerlan\Traits\ContainerInterface $options
	 * @param \GuzzleHttp\Client|null               $http
	 */
	public function __construct(ContainerInterface $options, Client $http = null){
		parent::__construct($options);

		if($http instanceof Client){
			$this->setClient($http);
		}
	}

	/** @inheritdoc */
	public function setClient(Client $http):HTTPClientInterface{
		$this->http = $http;

		return $this;
	}

	/** @inheritdoc */
	public function request(string $url, array $params = null, string $method = null, $body = null, array $headers = null):HTTPResponseInterface{

		try{
			$parsedURL = parse_url($url);
			$method    = strtoupper($method ?? 'POST');

			if(!isset($parsedURL['host']) || $parsedURL['scheme'] !== 'https'){
				trigger_error('invalid URL');
			}

			// @link http://docs.guzzlephp.org/en/stable/request-options.html
			$options = [
				'query'       => $params ?? [],
				'headers'     => $headers ?? [],
				'http_errors' => false, // no exceptions on HTTP errors plz
			];

			if(in_array($method, ['PATCH', 'POST', 'PUT', 'DELETE'], true)){

				if(is_scalar($body) || $body instanceof StreamInterface){
					$options['body'] = $body; // @codeCoverageIgnore
				}
				elseif(in_array($method, ['PATCH', 'POST', 'PUT'], true) && is_array($body)){
					$options['form_params'] = $body;
				}

			}

			$response = $this->http->request($method, explode('?', $url)[0], $options);

			$responseHeaders              = $this->parseResponseHeaders($response->getHeaders());
			$responseHeaders->statuscode  = $response->getStatusCode();
			$responseHeaders->statustext  = $response->getReasonPhrase();
			$responseHeaders->httpversion = $response->getProtocolVersion();

			return new HTTPResponse([
				'headers' => $responseHeaders,
				'body'    => $response->getBody(),
			]);

		}
		catch(\Exception $e){
			throw new HTTPClientException('fetch error: '.$e->getMessage());
		}

	}

	/**
	 * @param array $headers
	 *
	 * @return \stdClass
	 */
	protected function parseResponseHeaders(array $headers):\stdClass {
		$h = new \stdClass;

		foreach($headers as $k => $v){
			$h->{strtolower($k)} = $v[0] ?? null;
		}

		return $h;
	}

}
