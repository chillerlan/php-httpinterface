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

	/** @inheritdoc */
	public function __construct(ContainerInterface $options){
		parent::__construct($options);

		if(!isset($this->options->ca_info) || !is_file($this->options->ca_info)){
			throw new HTTPClientException('invalid CA file');
		}
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

			$method  = strtoupper($method ?? 'POST');
			$headers = $this->normalizeRequestHeaders($headers ?? []);

			if(in_array($method, ['PATCH', 'POST', 'PUT', 'DELETE']) && is_array($body)){
				$body = http_build_query($body, '', '&', PHP_QUERY_RFC1738);

				$headers['Content-Type']   = 'Content-Type: application/x-www-form-urlencoded';
				$headers['Content-length'] = 'Content-length: '.strlen($body);
			}

			$headers['Host']           = 'Host: '.$parsedURL['host'].(!empty($parsedURL['port']) ? ':'.$parsedURL['port'] : '');
			$headers['Connection']     = 'Connection: close';

			$params = $params ?? [];
			$url    = $url.(!empty($params) ? '?'.http_build_query($params) : '');

			$context = stream_context_create([
				'http' => [
					'method'           => $method,
					'header'           => $headers,
					'content'          => $body,
					'protocol_version' => '1.1',
					'user_agent'       => $this->options->user_agent,
					'max_redirects'    => 0,
					'timeout'          => 5,
				],
				'ssl' => [
					'cafile'              => $this->options->ca_info,
					'verify_peer'         => true,
					'verify_depth'        => 3,
					'peer_name'           => $parsedURL['host'],
					'ciphers'             => 'HIGH:!SSLv2:!SSLv3',
					'disable_compression' => true,
				],
			]);

			$response         = file_get_contents($url, false, $context);
			$responseHeaders  = get_headers($url, 1);

			return new HTTPResponse([
				'headers' => $this->parseResponseHeaders($responseHeaders),
				'body'    => trim($response),
			]);

		}
		catch(\Exception $e){
			throw new HTTPClientException($e->getMessage());
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
