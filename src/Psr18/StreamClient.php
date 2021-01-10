<?php
/**
 * Class StreamClient
 *
 * @filesource   StreamClient.php
 * @created      23.02.2019
 * @package      chillerlan\HTTP\Psr18
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 *
 * @phan-file-suppress PhanTypeInvalidThrowsIsInterface
 */

namespace chillerlan\HTTP\Psr18;

use ErrorException, Exception;
use Psr\Http\Message\{RequestInterface, ResponseInterface};

use function explode, file_get_contents, get_headers, in_array, intval, restore_error_handler,
	set_error_handler, stream_context_create, strtolower, substr, trim;

class StreamClient extends HTTPClientAbstract{

	/**
	 * @inheritDoc
	 * @throws \Psr\Http\Client\ClientExceptionInterface
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
				'protocol_version' => '1.1',
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

		$requestUri = (string)$uri->withFragment('');

		/** @phan-suppress-next-line PhanTypeMismatchArgumentInternal */
		set_error_handler(function(int $severity, string $msg, string $file, int $line):void{
			throw new ErrorException($msg, 0, $severity, $file, $line);
		});

		try{
			$responseBody    = file_get_contents($requestUri, false, $context);
			/** @phan-suppress-next-line PhanTypeMismatchArgumentInternal https://github.com/phan/phan/issues/3273 */
			$responseHeaders = $this->parseResponseHeaders(get_headers($requestUri, 1, $context));
		}
		catch(Exception $e){
			$this->logger->error('StreamClient error #'.$e->getCode().': '.$e->getMessage());

			throw new ClientException($e->getMessage(), $e->getCode());
		}

		restore_error_handler();

		$response = $this->responseFactory
			->createResponse($responseHeaders['statuscode'], $responseHeaders['statustext'])
			->withProtocolVersion($responseHeaders['httpversion'])
		;

		$response->getBody()->write($responseBody);
		$response->getBody()->rewind();

		return $response;
	}

	/**
	 *
	 */
	protected function getRequestHeaders(RequestInterface $request):array{
		$headers = [];

		foreach($request->getHeaders() as $name => $values){
			$name = strtolower($name);

			foreach($values as $value){
				$value = (string)$value;

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
