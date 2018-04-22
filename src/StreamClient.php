<?php
/**
 * Class StreamClient
 *
 * @filesource   StreamClient.php
 * @created      21.10.2017
 * @package      chillerlan\HTTP
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

use chillerlan\Traits\ContainerInterface;

class StreamClient extends HTTPClientAbstract{

	/**
	 * StreamClient constructor.
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
	}

	/** @inheritdoc */
	protected function getResponse():HTTPResponseInterface{
		$headers = $this->normalizeRequestHeaders($this->requestHeaders);

		if(in_array($this->requestMethod, ['PATCH', 'POST', 'PUT', 'DELETE']) && is_array($this->requestBody)){
			$this->requestBody = http_build_query($this->requestBody, '', '&', PHP_QUERY_RFC1738);

			$headers['Content-Type']   = 'Content-Type: application/x-www-form-urlencoded';
			$headers['Content-length'] = 'Content-length: '.strlen($this->requestBody);
		}

		$headers['Host']       = 'Host: '.$this->parsedURL['host'].(!empty($this->parsedURL['port']) ? ':'.$this->parsedURL['port'] : '');
		$headers['Connection'] = 'Connection: close';

		$url = $this->requestURL.(!empty($this->requestParams) ? '?'.$this->buildQuery($this->requestParams) : '');

		$context = stream_context_create([
			'http' => [
				'method'           => $this->requestMethod,
				'header'           => $headers,
				'content'          => $this->requestBody,
				'protocol_version' => '1.1',
				'user_agent'       => $this->options->user_agent,
				'max_redirects'    => 0,
				'timeout'          => 5,
			],
			'ssl' => [
				'cafile'              => $this->options->ca_info,
				'verify_peer'         => true,
				'verify_depth'        => 3,
				'peer_name'           => $this->parsedURL['host'],
				'ciphers'             => 'HIGH:!SSLv2:!SSLv3',
				'disable_compression' => true,
			],
		]);

		$response         = file_get_contents($url, false, $context);
		$responseHeaders  = get_headers($url, 1);

		return new HTTPResponse([
			'url'     => $url,
			'headers' => $this->parseResponseHeaders($responseHeaders),
			'body'    => trim($response),
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

			if($k === 0 && substr($v, 0, 4) === 'HTTP'){
				$status = explode(' ', $v, 3);

				$h->httpversion = explode('/', $status[0], 2)[1];
				$h->statuscode  = intval($status[1]);
				$h->statustext  = trim($status[2]);

				continue;
			}

			$h->{strtolower($k)} = $v;

		}

		return $h;
	}

}
