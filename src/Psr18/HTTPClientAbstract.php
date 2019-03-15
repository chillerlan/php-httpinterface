<?php
/**
 * Class HTTPClientAbstract
 *
 * @filesource   HTTPClientAbstract.php
 * @created      22.02.2019
 * @package      chillerlan\HTTP\Psr18
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr18;

use chillerlan\HTTP\{HTTPOptions, Psr7, Psr17};
use chillerlan\HTTP\Psr7\Request;
use chillerlan\HTTP\Psr17\ResponseFactory;
use chillerlan\Settings\SettingsContainerInterface;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface};
use Psr\Log\{LoggerAwareInterface, LoggerAwareTrait, LoggerInterface, NullLogger};

abstract class HTTPClientAbstract implements HTTPClientInterface, LoggerAwareInterface{
	use LoggerAwareTrait;

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
	 * CurlClient constructor.
	 *
	 * @param \chillerlan\Settings\SettingsContainerInterface|null $options
	 * @param \Psr\Http\Message\ResponseFactoryInterface|null      $responseFactory
	 * @param \Psr\Log\LoggerInterface|null                        $logger
	 */
	public function __construct(
		SettingsContainerInterface $options = null,
		ResponseFactoryInterface $responseFactory = null,
		LoggerInterface $logger = null
	){
		$this->options         = $options ?? new HTTPOptions;
		$this->responseFactory = $responseFactory ?? new ResponseFactory;
		$this->logger          = $logger ?? new NullLogger;
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
		$method    = \strtoupper($method ?? 'GET');
		$headers   = Psr7\normalize_request_headers($headers);
		$request   = new Request($method, Psr7\merge_query($uri, $query ?? []));

		if(\in_array($method, ['DELETE', 'PATCH', 'POST', 'PUT'], true) && $body !== null){

			if(\is_array($body) || \is_object($body)){

				if(!isset($headers['Content-type'])){
					$headers['Content-type'] = 'application/x-www-form-urlencoded';
				}

				if($headers['Content-type'] === 'application/x-www-form-urlencoded'){
					$body = \http_build_query($body, '', '&', \PHP_QUERY_RFC1738);
				}
				elseif($headers['Content-type'] === 'application/json'){
					$body = \json_encode($body);
				}
				else{
					$body = null; // @todo
				}

			}

			$request = $request->withBody(Psr17\create_stream((string)$body));
		}

		foreach($headers as $header => $value){
			$request = $request->withAddedHeader($header, $value);
		}

		return $this->sendRequest($request);
	}

}
