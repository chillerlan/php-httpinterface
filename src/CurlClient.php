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

	/** @inheritdoc */
	public function __construct(ContainerInterface $options){
		parent::__construct($options);

		if(!isset($this->options->ca_info) || !is_file($this->options->ca_info)){
			throw new HTTPClientException('invalid CA file');
		}

		$this->http = curl_init();

		curl_setopt_array($this->http, $this->options->curl_options);

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

	}

	/** @inheritdoc */
	public function request(string $url, array $params = null, string $method = null, $body = null, array $headers = null):HTTPResponse{
		$this->responseHeaders = new \stdClass;

		try{
			$parsedURL = parse_url($url);

			if(!isset($parsedURL['host']) || $parsedURL['scheme'] !== 'https'){
				trigger_error('invalid URL');
			}

			$method    = strtoupper($method ?? 'POST');
			$headers   = $this->normalizeRequestHeaders($headers ?? []);
			$options   = [CURLOPT_CUSTOMREQUEST => $method];

			if(in_array($method, ['PATCH', 'POST', 'PUT', 'DELETE'], true)){

				if($method === 'POST'){
					$options = [CURLOPT_POST => true];

					if(!isset($headers['Content-type']) && is_array($body)){
						$headers += ['Content-type: application/x-www-form-urlencoded'];
						$body = http_build_query($body, '', '&', PHP_QUERY_RFC1738);
					}

				}

				$options += [CURLOPT_POSTFIELDS => $body];
			}

			$headers += [
				'Host: '.$parsedURL['host'],
				'Connection: close',
			];

			$params = $params ?? [];
			$url    = $url.(!empty($params) ? '?'.http_build_query($params) : '');

			$options += [
				CURLOPT_URL => $url,
				CURLOPT_HTTPHEADER => $headers,
				CURLOPT_HEADERFUNCTION => [$this, 'headerLine'],
			];

			curl_setopt_array($this->http, $options);

			$response = curl_exec($this->http);

			return new HTTPResponse([
				'headers' => $this->responseHeaders,
				'body'    => $response,
			]);

		}
		catch(\Exception $e){
			throw new HTTPClientException($e->getMessage());
		}

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
