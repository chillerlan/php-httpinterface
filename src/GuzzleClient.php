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

use chillerlan\Traits\ImmutableSettingsInterface;
use GuzzleHttp\Client;
use Psr\Http\Message\StreamInterface;

/**
 * @property \GuzzleHttp\Client $http
 */
class GuzzleClient extends HTTPClientAbstract{

	/**
	 * GuzzleClient constructor.
	 *
	 * @param \chillerlan\Traits\ImmutableSettingsInterface $options
	 * @param \GuzzleHttp\Client|null               $http
	 */
	public function __construct(ImmutableSettingsInterface $options, Client $http = null){
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
	protected function getResponse():HTTPResponseInterface{
		// @link http://docs.guzzlephp.org/en/stable/request-options.html
		$options = [
			'query'       => $this->requestParams,
			'headers'     => $this->requestHeaders,
			'http_errors' => false, // no exceptions on HTTP errors plz
		];

		if(in_array($this->requestMethod, ['PATCH', 'POST', 'PUT', 'DELETE'], true)){

			if(is_scalar($this->requestBody) || $this->requestBody instanceof StreamInterface){
				$options['body'] = $this->requestBody; // @codeCoverageIgnore
			}
			elseif(in_array($this->requestMethod, ['PATCH', 'POST', 'PUT'], true) && is_array($this->requestBody)){
				$options['form_params'] = $this->requestBody;
			}

		}

		$url = explode('?', $this->requestURL)[0];

		$response = $this->http->request($this->requestMethod, $url, $options); // @todo: merge query params

		$responseHeaders              = $this->parseResponseHeaders($response->getHeaders());
		$responseHeaders->statuscode  = $response->getStatusCode();
		$responseHeaders->statustext  = $response->getReasonPhrase();
		$responseHeaders->httpversion = $response->getProtocolVersion();

		return new HTTPResponse([
			'url'     => $url,
			'headers' => $responseHeaders,
			'body'    => $response->getBody(),
		]);
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
