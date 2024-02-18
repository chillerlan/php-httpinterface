<?php
/**
 * Class StreamClient
 *
 * @created      23.02.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr18;

use chillerlan\HTTP\Utils\HeaderUtil;
use Psr\Http\Message\{RequestInterface, ResponseInterface};
use function explode, file_get_contents, get_headers, in_array, intval, restore_error_handler,
	set_error_handler, stream_context_create, strtolower, str_starts_with, trim;

/**
 *
 */
class StreamClient extends HTTPClientAbstract{

	/**
	 * @inheritDoc
	 */
	public function sendRequest(RequestInterface $request):ResponseInterface{

		$errorHandler = function(int $errno, string $errstr):bool{
			$this->logger->error('StreamClient error #'.$errno.': '.$errstr);

			throw new ClientException($errstr, $errno);
		};

		set_error_handler($errorHandler);

		$context      = stream_context_create($this->getContextOptions($request));
		$requestUri   = (string)$request->getUri()->withFragment('');
		$responseBody = file_get_contents($requestUri, false, $context);
		$response     = $this->createResponse(get_headers($requestUri, true, $context));

		restore_error_handler();

		$body = $this->streamFactory !== null
			? $this->streamFactory->createStream()
			: $response->getBody()
		;

		$body->write($responseBody);
		$body->rewind();

		return $response->withBody($body);
	}

	/**
	 *
	 */
	protected function getContextOptions(RequestInterface $request):array{
		$method = $request->getMethod();
		$body   = in_array($method, ['DELETE', 'PATCH', 'POST', 'PUT'], true)
			? $request->getBody()->getContents()
			: null;

		$options = [
			'http' => [
				'method'           => $method,
				'header'           => $this->getRequestHeaders($request),
				'content'          => $body,
				'protocol_version' => $request->getProtocolVersion(),
				'user_agent'       => $this->options->user_agent,
				'max_redirects'    => 0,
				'timeout'          => 5,
			],
			'ssl'  => [
				'verify_peer'         => $this->options->ssl_verifypeer,
				'verify_depth'        => 3,
				'peer_name'           => $request->getUri()->getHost(),
				'ciphers'             => 'HIGH:!SSLv2:!SSLv3',
				'disable_compression' => true,
			],
		];

		$ca                  = ($this->options->ca_info_is_path) ? 'capath' : 'cafile';
		$options['ssl'][$ca] = $this->options->ca_info;

		return $options;
	}

	/**
	 *
	 */
	protected function getRequestHeaders(RequestInterface $request):array{
		$headers = [];

		foreach($request->getHeaders() as $name => $values){
			$name = strtolower($name);

			foreach($values as $value){
				// cURL requires a special format for empty headers.
				// See https://github.com/guzzle/guzzle/issues/1882 for more details.
				$headers[] = ($value === '') ? $name.';' : $name.': '.$value;
			}
		}

		return $headers;
	}

	/**
	 * @param string[] $headers
	 */
	protected function createResponse(array $headers):ResponseInterface{
		$h = [];

		$httpversion = '';
		$statuscode  = 0;
		$statustext  = '';

		foreach($headers as $k => $v){

			if($k === 0 && str_starts_with($v, 'HTTP')){
				$status = explode(' ', $v, 3);

				$httpversion = explode('/', $status[0], 2)[1];
				$statuscode  = intval($status[1]);
				$statustext  = trim($status[2]);

				continue;
			}

			$h[$k] = $v;
		}

		$response = $this->responseFactory
			->createResponse($statuscode, $statustext)
			->withProtocolVersion($httpversion)
		;

		foreach(HeaderUtil::normalize($h) as $k => $v){
			$response = $response->withAddedHeader($k, $v);
		}

		return $response;
	}

}
