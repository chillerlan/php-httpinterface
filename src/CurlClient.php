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

namespace chillerlan\HTTP;

use chillerlan\HTTP\Psr17\{RequestFactory, ResponseFactory, StreamFactory};
use chillerlan\Settings\SettingsContainerInterface;
use Psr\Http\Message\{RequestFactoryInterface, RequestInterface, ResponseFactoryInterface, ResponseInterface, StreamFactoryInterface};

class CurlClient implements HTTPClientInterface{

	/**
	 * @var \chillerlan\HTTP\HTTPOptions
	 */
	protected $options;

	/**
	 * @var \Psr\Http\Message\RequestFactoryInterface
	 */
	protected $requestFactory;

	/**
	 * @var \Psr\Http\Message\ResponseFactoryInterface
	 */
	protected $responseFactory;

	/**
	 * @var \Psr\Http\Message\StreamFactoryInterface
	 */
	protected $streamFactory;

	/**
	 * CurlClient constructor.
	 *
	 * @param \chillerlan\Settings\SettingsContainerInterface|null $options
	 * @param \Psr\Http\Message\RequestFactoryInterface|null       $requestFactory
	 * @param \Psr\Http\Message\ResponseFactoryInterface|null      $responseFactory
	 * @param \Psr\Http\Message\StreamFactoryInterface|null        $streamFactory
	 */
	public function __construct(
		SettingsContainerInterface $options = null,
		RequestFactoryInterface $requestFactory = null,
		ResponseFactoryInterface $responseFactory = null,
		StreamFactoryInterface $streamFactory = null
	){
		$this->options         = $options ?? new HTTPOptions;
		$this->requestFactory  = $requestFactory ?? new RequestFactory;
		$this->responseFactory = $responseFactory ?? new ResponseFactory;
		$this->streamFactory   = $streamFactory ?? new StreamFactory;
	}

	/**
	 * Sends a PSR-7 request.
	 *
	 * @param \Psr\Http\Message\RequestInterface $request
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 *
	 * @throws \Psr\Http\Client\ClientExceptionInterface If an error happens during processing the request.
	 * @throws \Exception                                If processing the request is impossible (eg. bad configuration).
	 */
	public function sendRequest(RequestInterface $request):ResponseInterface{
		$handle = new CurlHandle($request, $this->responseFactory->createResponse(), $this->options);
		$handle->init();

		curl_exec($handle->ch);

		$errno = curl_errno($handle->ch);

		if($errno !== CURLE_OK){
			$error = curl_error($handle->ch);

			$network_errors = [
				CURLE_COULDNT_RESOLVE_PROXY,
				CURLE_COULDNT_RESOLVE_HOST,
				CURLE_COULDNT_CONNECT,
				CURLE_OPERATION_TIMEOUTED,
				CURLE_SSL_CONNECT_ERROR,
				CURLE_GOT_NOTHING,
			];

			if(in_array($errno, $network_errors, true)){
				throw new NetworkException($error, $request);
			}

			throw new RequestException($error, $request);
		}

		$handle->close();
		$handle->response->getBody()->rewind();

		return $handle->response;

	}

	/**
	 * @todo: files, content-type
	 *
	 * @param string      $uri
	 * @param string|null $method
	 * @param array|null  $query
	 * @param mixed|null  $body
	 * @param array|null  $headers
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function request(string $uri, string $method = null, array $query = null, $body = null, array $headers = null):ResponseInterface{
		$method    = strtoupper($method ?? 'GET');
		$headers   = Psr7\normalize_request_headers($headers);
		$request   = $this->requestFactory->createRequest($method, Psr7\merge_query($uri, $query ?? []));

		if(in_array($method, ['DELETE', 'PATCH', 'POST', 'PUT'], true) && $body !== null){

			if(is_array($body) || is_object($body)){

				if(!isset($headers['Content-type'])){
					$headers['Content-type'] = 'application/x-www-form-urlencoded';
				}

				if($headers['Content-type'] === 'application/x-www-form-urlencoded'){
					$body = http_build_query($body, '', '&', PHP_QUERY_RFC1738);
				}
				elseif($headers['Content-type'] === 'application/json'){
					$body = json_encode($body);
				}
				else{
					$body = null; // @todo
				}

			}

			$request = $request->withBody($this->streamFactory->createStream((string)$body));
		}

		foreach($headers as $header => $value){
			$request = $request->withAddedHeader($header, $value);
		}

		return $this->sendRequest($request);
	}

}
