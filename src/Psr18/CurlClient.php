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
use function in_array, sprintf;
use const CURLE_OK;

class CurlClient extends HTTPClientAbstract{

	protected CurlHandle $handle;

	/**
	 * @inheritDoc
	 */
	public function sendRequest(RequestInterface $request):ResponseInterface{
		$stream       = $this->streamFactory !== null ? $this->streamFactory->createStream() : null;
		$this->handle = new CurlHandle($request, $this->responseFactory->createResponse(), $this->options, $stream);
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

		return $this->handle->getResponse();
	}

}
