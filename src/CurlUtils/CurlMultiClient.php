<?php
/**
 * Class CurlMultiClient
 *
 * @created      30.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\CurlUtils;

use chillerlan\HTTP\{HTTPOptions, Psr17\ResponseFactory, Psr18\ClientException};
use chillerlan\Settings\SettingsContainerInterface;
use Psr\Http\Message\{RequestInterface, ResponseFactoryInterface};
use Psr\Log\{LoggerAwareInterface, LoggerAwareTrait, LoggerInterface, NullLogger};

use function array_shift, curl_close, curl_multi_add_handle, curl_multi_close, curl_multi_exec,
	curl_multi_info_read, curl_multi_init, curl_multi_remove_handle, curl_multi_select, curl_multi_setopt,
	is_resource, usleep;

use const CURLM_OK, CURLMOPT_MAXCONNECTS, CURLMOPT_PIPELINING, CURLPIPE_MULTIPLEX;

final class CurlMultiClient implements LoggerAwareInterface{
	use LoggerAwareTrait;

	/** @var \chillerlan\Settings\SettingsContainerInterface|\chillerlan\HTTP\HTTPOptions */
	private SettingsContainerInterface $options;

	private ResponseFactoryInterface $responseFactory;

	private MultiResponseHandlerInterface $multiResponseHandler;

	/**
	 * the curl_multi master handle
	 *
	 * @var resource
	 */
	private $curl_multi;

	/**
	 * An array of RequestInterface to run
	 *
	 * @var \Psr\Http\Message\RequestInterface[]
	 */
	private array $requests = [];

	/**
	 * the stack of running handles
	 *
	 * @var \chillerlan\HTTP\CurlUtils\CurlMultiHandle[]
	 */
	private array $handles = [];

	/**
	 *
	 */
	private int $handleCounter = 0;

	/**
	 * CurlMultiClient constructor.
	 */
	public function __construct(
		MultiResponseHandlerInterface $multiResponseHandler,
		SettingsContainerInterface $options = null,
		ResponseFactoryInterface $responseFactory = null,
		LoggerInterface $logger = null
	){
		$this->multiResponseHandler = $multiResponseHandler;
		$this->options              = $options ?? new HTTPOptions;
		$this->responseFactory      = $responseFactory ?? new ResponseFactory;
		$this->logger               = $logger ?? new NullLogger;
		$this->curl_multi           = curl_multi_init();

		$curl_multi_options = [
			CURLMOPT_PIPELINING  => CURLPIPE_MULTIPLEX,
			CURLMOPT_MAXCONNECTS => $this->options->window_size,
		] + $this->options->curl_multi_options;

		foreach($curl_multi_options as $k => $v){
			curl_multi_setopt($this->curl_multi, $k, $v);
		}

	}

	/**
	 * close an existing cURL multi handle on exit
	 */
	public function __destruct(){
		$this->close();
	}

	/**
	 * @return void
	 */
	public function close():void{

		if(is_resource($this->curl_multi)){
			curl_multi_close($this->curl_multi);
		}

	}

	/**
	 * @param \Psr\Http\Message\RequestInterface $request
	 *
	 * @return \chillerlan\HTTP\CurlUtils\CurlMultiClient
	 */
	public function addRequest(RequestInterface $request):CurlMultiClient{
		$this->requests[] = $request;

		return $this;
	}

	/**
	 * @param \Psr\Http\Message\RequestInterface[] $stack
	 *
	 * @return \chillerlan\HTTP\CurlUtils\CurlMultiClient
	 */
	public function addRequests(iterable $stack):CurlMultiClient{

		foreach($stack as $request){

			if($request instanceof RequestInterface){
				$this->requests[] = $request;
			}

		}

		return $this;
	}

	/**
	 * @phan-suppress PhanTypeInvalidThrowsIsInterface
	 * @throws \Psr\Http\Client\ClientExceptionInterface
	 */
	public function process():CurlMultiClient{

		if(empty($this->requests)){
			throw new ClientException('request stack is empty');
		}

		// shoot out the first batch of requests
		for($i = 0; $i < $this->options->window_size; $i++){
			$this->createHandle();
		}

		// ...and process the stack
		do{
			$status = curl_multi_exec($this->curl_multi, $active);

			if($active){
				curl_multi_select($this->curl_multi, $this->options->timeout);
			}

			while($state = curl_multi_info_read($this->curl_multi)){
				$id     = (int)$state['handle'];
				$handle = $this->handles[$id];
				$result = $handle->handleResponse();

				curl_multi_remove_handle($this->curl_multi, $state['handle']);
				curl_close($state['handle']);
				unset($this->handles[$id]);

				if($result instanceof RequestInterface && $handle->getRetries() < $this->options->retries){
					$this->createHandle($result, $handle->getID(), $handle->addRetry());

					continue;
				}

				$this->createHandle();
			}

		}
		while($active && $status === CURLM_OK);

		return $this;
	}

	/**
	 *
	 */
	private function createHandle(RequestInterface $request = null, int $id = null, int $retries = null):void{

		if($request === null){

			if(empty($this->requests)){
				return;
			}

			$request = array_shift($this->requests);
		}

		$handle = new CurlMultiHandle(
			$this->multiResponseHandler,
			$request,
			$this->responseFactory->createResponse(),
			$this->options
		);

		$handle
			->setID($id ?? $this->handleCounter++)
			->setRetries($retries ?? 1)
			->init()
		;

		$curl = $handle->getCurlResource();

		curl_multi_add_handle($this->curl_multi, $curl);

		$this->handles[(int)$curl] = $handle;

		if($this->options->sleep > 0){
			usleep($this->options->sleep);
		}

	}

}
