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
use function curl_errno, curl_error, curl_exec, in_array, sprintf;

use const CURLE_OK;

class CurlClient extends HTTPClientAbstract{

	protected CurlHandle $handle;

	/**
	 * @inheritDoc
	 */
	public function sendRequest(RequestInterface $request):ResponseInterface{
		$this->logger->debug(sprintf("\n----HTTP-REQUEST----\n%s", message_to_string($request)));

		$this->handle = $this->createHandle($request);
		$errno        = $this->handle->exec();

		if($errno !== CURLE_OK){
			$error = $this->handle->getError();

			$this->logger->error(sprintf('cURL error #%s: %s', $errno, $error));

			if(in_array($errno, $this->handle::CURL_NETWORK_ERRORS, true)){
				throw new NetworkException($error, $request);
			}

			throw new RequestException($error, $request);
		}

		$this->handle->close();
		$response = $this->handle->getResponse();
		$this->logger->debug(sprintf("\n----HTTP-RESPONSE---\n%s", message_to_string($response)));

		return $response;
	}

	/**
	 *
	 */
	protected function createHandle(RequestInterface $request):CurlHandle{
		return new CurlHandle($request, $this->responseFactory->createResponse(), $this->options);
	}

}
