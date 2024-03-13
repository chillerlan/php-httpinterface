<?php
/**
 * Class CurlMultiClient
 *
 * @created      30.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTP;

use chillerlan\Settings\SettingsContainerInterface;
use Psr\Http\Message\{RequestInterface, ResponseFactoryInterface};
use Psr\Log\{LoggerInterface, NullLogger};
use CurlMultiHandle as CMH;
use function array_shift, count, curl_close, curl_multi_add_handle, curl_multi_close, curl_multi_exec,
	curl_multi_info_read, curl_multi_init, curl_multi_remove_handle, curl_multi_select, curl_multi_setopt, sprintf, usleep;
use const CURLM_OK, CURLMOPT_MAXCONNECTS, CURLMOPT_PIPELINING, CURLPIPE_MULTIPLEX;

/**
 * Curl multi http client
 */
class CurlMultiClient{

	/**
	 * the cURL multi handle instance
	 */
	protected CMH $curl_multi;

	/**
	 * An array of RequestInterface to run
	 */
	protected array $requests = [];

	/**
	 * the stack of running handles
	 *
	 * cURL instance id => [counter, retries, handle]
	 */
	protected array $handles = [];

	/**
	 * the request counter (request ID/order in multi response handler)
	 */
	protected int $counter = 0;

	/**
	 * CurlMultiClient constructor.
	 */
	public function __construct(
		protected MultiResponseHandlerInterface          $multiResponseHandler,
		protected ResponseFactoryInterface               $responseFactory,
		protected HTTPOptions|SettingsContainerInterface $options = new HTTPOptions,
		protected LoggerInterface                        $logger = new NullLogger,
	){
		$this->curl_multi = curl_multi_init();

		$this->initCurlMultiOptions();
	}

	protected function initCurlMultiOptions():void{

		$curl_multi_options = [
			CURLMOPT_PIPELINING  => CURLPIPE_MULTIPLEX,
			CURLMOPT_MAXCONNECTS => $this->options->window_size,
		];

		$curl_multi_options += $this->options->curl_multi_options;

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
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function setLogger(LoggerInterface $logger):static{
		$this->logger = $logger;

		return $this;
	}

	/**
	 * closes the handle
	 */
	public function close():static{
		curl_multi_close($this->curl_multi);

		return $this;
	}

	/**
	 * adds a request to the stack
	 */
	public function addRequest(RequestInterface $request):static{
		$this->requests[] = $request;

		return $this;
	}

	/**
	 * adds multiple requests to the stack
	 *
	 * @param \Psr\Http\Message\RequestInterface[] $stack
	 */
	public function addRequests(iterable $stack):static{

		foreach($stack as $request){

			if($request instanceof RequestInterface){
				$this->requests[] = $request;
			}

		}

		return $this;
	}

	/**
	 * processes the stack
	 *
	 * @throws \Psr\Http\Client\ClientExceptionInterface
	 */
	public function process():static{

		if(empty($this->requests)){
			throw new ClientException('request stack is empty');
		}

		// shoot out the first batch of requests
		for($i = 0; $i < $this->options->window_size; $i++){
			$this->createHandle();
		}

		// ...and process the stack
		do{
			// $still_running is not a "flag" as the documentation states, but the number of currently active handles
			$status = curl_multi_exec($this->curl_multi, $active_handles);

			if(curl_multi_select($this->curl_multi, $this->options->timeout) === -1){
				usleep(100000); // sleep a bit (100ms)
			}

			// this assignment-in-condition is intentional btw
			while($state = curl_multi_info_read($this->curl_multi)){
				$this->resolve((int)$state['handle']);

				curl_multi_remove_handle($this->curl_multi, $state['handle']);
				curl_close($state['handle']);
			}

		}
		while($active_handles > 0 && $status === CURLM_OK);

		// for some reason not all requests were processed (errors while adding to curl_multi)
		if(!empty($this->requests)){
			$this->logger->warning(sprintf('%s request(s) in the stack could not be processed', count($this->requests)));
		}

		return $this;
	}

	/**
	 * resolves the handle, calls the response handler callback and creates the next handle
	 */
	protected function resolve(int $handleID):void{
		[$counter, $retries, $handle] = $this->handles[$handleID];

		$result = $this->multiResponseHandler->handleResponse(
			$handle->getResponse(),
			$handle->getRequest(),
			$counter,
			$handle->getInfo(),
		);

		$handle->close();
		unset($this->handles[$handleID]);

		($result instanceof RequestInterface && $retries < $this->options->retries)
			? $this->createHandle($result, $counter, ++$retries)
			: $this->createHandle();
	}

	/**
	 * creates a new request handle
	 */
	protected function createHandle(
		RequestInterface|null $request = null,
		int|null              $counter = null,
		int|null              $retries = null,
	):void{

		if($request === null){

			if(empty($this->requests)){
				return;
			}

			$request = array_shift($this->requests);
		}

		$handle = (new CurlHandle($request, $this->responseFactory->createResponse(), $this->options));

		// initialize the handle get the cURL resource and add it to the multi handle
		$error = curl_multi_add_handle($this->curl_multi, $handle->init());

		if($error !== CURLM_OK){
			$this->addRequest($request); // re-add the request

			$this->logger->error(sprintf('could not attach current handle to curl_multi instance. (error: %s)', $error));

			return;
		}

		$this->handles[$handle->getHandleID()] = [($counter ?? ++$this->counter) , ($retries ?? 0), $handle];

		if($this->options->sleep > 0){
			usleep($this->options->sleep);
		}

	}

}
