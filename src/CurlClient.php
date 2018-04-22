<?php
/**
 * Class CurlClient
 *
 * @filesource   CurlClient.php
 * @created      21.10.2017
 * @package      chillerlan\HTTP
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

use chillerlan\Traits\ContainerInterface;

/**
 * @property resource $http
 */
class CurlClient extends HTTPClientAbstract{

	/**
	 * @var \stdClass
	 */
	protected $responseHeaders;

	/**
	 * CurlClient constructor.
	 *
	 * @param \chillerlan\Traits\ContainerInterface $options
	 *
	 * @throws \chillerlan\HTTP\HTTPClientException
	 */
	public function __construct(ContainerInterface $options){
		parent::__construct($options);

		if(!isset($this->options->ca_info) || !is_file($this->options->ca_info)){
			throw new HTTPClientException('invalid CA file');
		}

		$this->http = curl_init();

		curl_setopt_array($this->http, [
			CURLOPT_HEADER         => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_PROTOCOLS      => CURLPROTO_HTTP|CURLPROTO_HTTPS,
			CURLOPT_CAINFO         => $this->options->ca_info,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_TIMEOUT        => 5,
			CURLOPT_USERAGENT      => $this->options->user_agent,
		]);

		curl_setopt_array($this->http, $this->options->curl_options);
	}

	/** @inheritdoc */
	protected function getResponse():HTTPResponseInterface{
		$this->responseHeaders = new \stdClass;

		$headers = $this->normalizeRequestHeaders($this->requestHeaders);

		if(in_array($this->requestMethod, ['PATCH', 'POST', 'PUT', 'DELETE'])){

			$options = in_array($this->requestMethod, ['PATCH', 'PUT', 'DELETE'])
				? [CURLOPT_CUSTOMREQUEST => $this->requestMethod]
				: [CURLOPT_POST => true];


			if(!isset($headers['Content-type']) && $this->requestMethod === 'POST' && is_array($this->requestBody)){
				$headers += ['Content-type: application/x-www-form-urlencoded'];
				$this->requestBody = http_build_query($this->requestBody, '', '&', PHP_QUERY_RFC1738);
			}

			$options += [CURLOPT_POSTFIELDS => $this->requestBody];
		}
		else{
			$options = [CURLOPT_CUSTOMREQUEST => $this->requestMethod];
		}

		$headers += [
			'Host: '.$this->parsedURL['host'],
			'Connection: close',
		];

		parse_str($this->parsedURL['query'] ?? '', $parsedquery);
		$params = array_merge($parsedquery, $this->requestParams);

		$url = $this->requestURL.(!empty($params) ? '?'.http_build_query($params) : '');

		$options += [
			CURLOPT_URL => $url,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_HEADERFUNCTION => [$this, 'headerLine'],
		];

		curl_setopt_array($this->http, $options);

		$response  = curl_exec($this->http);
		$curl_info = curl_getinfo($this->http);

		return new HTTPResponse([
			'url'       => $url,
			'curl_info' => $curl_info,
			'headers'   => $this->responseHeaders,
			'body'      => $response,
		]);
	}

	/**
	 * @param resource $curl
	 * @param string   $header_line
	 *
	 * @return int
	 *
	 * @link http://php.net/manual/function.curl-setopt.php CURLOPT_HEADERFUNCTION
	 */
	protected function headerLine(/** @noinspection PhpUnusedParameterInspection */$curl, $header_line){
		$header = explode(':', $header_line, 2);

		if(count($header) === 2){
			$this->responseHeaders->{trim(strtolower($header[0]))} = trim($header[1]);
		}
		elseif(substr($header_line, 0, 4) === 'HTTP'){
			$status = explode(' ', $header_line, 3);

			$this->responseHeaders->httpversion = explode('/', $status[0], 2)[1];
			$this->responseHeaders->statuscode  = intval($status[1]);
			$this->responseHeaders->statustext  = trim($status[2]);
		}

		return strlen($header_line);
	}

}
