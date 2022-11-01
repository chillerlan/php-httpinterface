<?php
/**
 * Class StreamClient
 *
 * @created      23.02.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 *
 * @phan-file-suppress PhanTypeInvalidThrowsIsInterface
 */

namespace chillerlan\HTTP\Psr18;

use Psr\Http\Message\{RequestInterface, ResponseInterface};
use function explode, file_get_contents, get_headers, in_array, intval, restore_error_handler,
	set_error_handler, stream_context_create, strtolower, substr, trim;

class StreamClient extends HTTPClientAbstract{

	/**
	 * @inheritDoc
	 * @throws \Psr\Http\Client\ClientExceptionInterface|\ErrorException
	 *
	 */
	public function sendRequest(RequestInterface $request):ResponseInterface{
		$uri     = $request->getUri();
		$method  = $request->getMethod();
		$headers = $this->getRequestHeaders($request);

		$body = in_array($method, ['DELETE', 'PATCH', 'POST', 'PUT'], true)
			? $request->getBody()->getContents()
			: null;

		$context = stream_context_create([
			'http' => [
				'method'           => $method,
				'header'           => $headers,
				'content'          => $body,
#				'protocol_version' => '1.1',
				'user_agent'       => $this->options->user_agent,
				'max_redirects'    => 0,
				'timeout'          => 5,
			],
			'ssl'  => [
				'cafile'              => $this->options->ca_info,
				'verify_peer'         => $this->options->ssl_verifypeer,
				'verify_depth'        => 3,
				'peer_name'           => $uri->getHost(),
				'ciphers'             => 'HIGH:!SSLv2:!SSLv3',
				'disable_compression' => true,
			],
		]);

		$errorHandler = function(int $errno, string $errstr):bool{
			$this->logger->error('StreamClient error #'.$errno.': '.$errstr);

			throw new ClientException($errstr, $errno);
		};

		set_error_handler($errorHandler);

		$requestUri      = (string)$uri->withFragment('');
		$responseBody    = file_get_contents($requestUri, false, $context);
		$responseHeaders = $this->parseResponseHeaders(get_headers($requestUri, true, $context));

		restore_error_handler();

		$response = $this->responseFactory
			->createResponse($responseHeaders['statuscode'], $responseHeaders['statustext'])
			->withProtocolVersion($responseHeaders['httpversion'])
		;

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
	protected function getRequestHeaders(RequestInterface $request):array{
		$headers = [];

		foreach($request->getHeaders() as $name => $values){
			$name = strtolower($name);

			foreach($values as $value){
				// cURL requires a special format for empty headers.
				// See https://github.com/guzzle/guzzle/issues/1882 for more details.
				$headers[] = $value === '' ? $name.';' : $name.': '.$value;
			}
		}

		return $headers;
	}

	/**
	 * @param string[] $headers
	 */
	protected function parseResponseHeaders(array $headers):array{
		$h = [];

		foreach($headers as $k => $v){

			if($k === 0 && substr($v, 0, 4) === 'HTTP'){
				$status = explode(' ', $v, 3);

				$h['httpversion'] = explode('/', $status[0], 2)[1];
				$h['statuscode']  = intval($status[1]);
				$h['statustext']  = trim($status[2]);

				continue;
			}

			$h[strtolower($k)] = $v;
		}

		return $h;
	}

}
