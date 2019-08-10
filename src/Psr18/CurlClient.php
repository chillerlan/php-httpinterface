<?php
/**
 * Class HTTPClient
 *
 * @filesource   HTTPClient.php
 * @created      27.08.2018
 * @package      chillerlan\HTTP
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr18;

use Psr\Http\Message\{RequestInterface, ResponseInterface};

use function curl_errno, curl_error, curl_exec, in_array;

use const CURLE_COULDNT_CONNECT, CURLE_COULDNT_RESOLVE_HOST, CURLE_COULDNT_RESOLVE_PROXY, CURLE_GOT_NOTHING,
	CURLE_OK, CURLE_OPERATION_TIMEOUTED, CURLE_SSL_CONNECT_ERROR;

class CurlClient extends HTTPClientAbstract{

	/**
	 * @inheritDoc
	 */
	public function sendRequest(RequestInterface $request):ResponseInterface{
		/** @var \chillerlan\HTTP\Psr18\CurlHandle $handle */
		$handle = new $this->options->curlHandle($request, $this->responseFactory->createResponse(), $this->options);
		$handle->init();

		curl_exec($handle->curl);

		$errno = curl_errno($handle->curl);

		if($errno !== CURLE_OK){
			$error = curl_error($handle->curl);

			$network_errors = [
				CURLE_COULDNT_RESOLVE_PROXY,
				CURLE_COULDNT_RESOLVE_HOST,
				CURLE_COULDNT_CONNECT,
				CURLE_OPERATION_TIMEOUTED,
				CURLE_SSL_CONNECT_ERROR,
				CURLE_GOT_NOTHING,
			];

			$this->logger->error('cURL error #'.$errno.': '.$error);

			if(in_array($errno, $network_errors, true)){
				throw new NetworkException($error, $request);
			}

			throw new RequestException($error, $request);
		}

		$handle->close();
		$handle->response->getBody()->rewind();

		return $handle->response;
	}

}
