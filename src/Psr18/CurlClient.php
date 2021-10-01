<?php
/**
 * Class HTTPClient
 *
 * @created      27.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr18;

use chillerlan\HTTP\CurlUtils\CurlHandle;
use Psr\Http\Message\{RequestInterface, ResponseInterface};

use function chillerlan\HTTP\Utils\message_to_string;
use function curl_errno, curl_error, curl_exec, in_array;

use const CURLE_OK;

class CurlClient extends HTTPClientAbstract{

	protected CurlHandle $handle;

	/**
	 * @inheritDoc
	 */
	public function sendRequest(RequestInterface $request):ResponseInterface{
		$this->logger->debug("\n----HTTP-REQUEST----\n".message_to_string($request));

		$this->handle = $this->createHandle($request);
		$this->handle->init();

		$curl = $this->handle->getCurlResource();

		curl_exec($curl);

		$errno = curl_errno($curl);

		if($errno !== CURLE_OK){
			$error = curl_error($curl);

			$this->logger->error('cURL error #'.$errno.': '.$error);

			if(in_array($errno, $this->handle::CURL_NETWORK_ERRORS, true)){
				throw new NetworkException($error, $request);
			}

			throw new RequestException($error, $request);
		}

		$this->handle->close();
		$response = $this->handle->getResponse();
		$this->logger->debug("\n----HTTP-RESPONSE---\n".message_to_string($response));
		$response->getBody()->rewind();

		return $response;
	}

	/**
	 *
	 */
	protected function createHandle(RequestInterface $request):CurlHandle{
		return new CurlHandle($request, $this->responseFactory->createResponse(), $this->options);
	}

}
