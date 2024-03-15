<?php
/**
 * Class HTTPClient
 *
 * @created      27.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTP;

use Psr\Http\Message\{RequestInterface, ResponseInterface};
use function in_array, sprintf;
use const CURLE_OK;

/**
 * A "simple" cURL http client
 */
class CurlClient extends HTTPClientAbstract{

	/**
	 * @inheritDoc
	 */
	public function sendRequest(RequestInterface $request):ResponseInterface{

		$handle = new CurlHandle(
			$request,
			$this->responseFactory->createResponse(),
			$this->options,
			$this->streamFactory?->createStream(),
		);

		$errno = $handle->exec();

		if($errno !== CURLE_OK){
			$error = $handle->getError();

			$this->logger->error(sprintf('cURL error #%s: %s', $errno, $error));

			if(in_array($errno, $handle::CURL_NETWORK_ERRORS, true)){
				throw new NetworkException($error, $request);
			}

			throw new RequestException($error, $request);
		}

		$handle->close();

		return $handle->getResponse();
	}

}
