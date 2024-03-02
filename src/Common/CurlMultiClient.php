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

namespace chillerlan\HTTP\Common;

use chillerlan\HTTP\Psr18\ClientException;
use chillerlan\HTTP\HTTPOptions;
use chillerlan\Settings\SettingsContainerInterface;
use Psr\Http\Message\{RequestInterface, ResponseFactoryInterface};
use Psr\Log\{LoggerAwareInterface, LoggerInterface, NullLogger};
use CurlMultiHandle as CMH;
use function array_shift, curl_close, curl_multi_add_handle, curl_multi_close, curl_multi_exec,
	curl_multi_info_read, curl_multi_init, curl_multi_remove_handle, curl_multi_select, curl_multi_setopt, usleep;
use const CURLM_OK, CURLMOPT_MAXCONNECTS, CURLMOPT_PIPELINING, CURLPIPE_MULTIPLEX;

/**
 * Curl multi http client
 */
class CurlMultiClient implements LoggerAwareInterface{

	protected CMH|null $curl_multi    = null;
	protected int      $handleCounter = 0;

	/**
	 * An array of RequestInterface to run
	 */
	protected array $requests = [];

	/**
	 * the stack of running handles
	 *
	 * @var \chillerlan\HTTP\Common\CurlMultiHandle[]
	 */
	protected array $handles = [];

	/**
	 * CurlMultiClient constructor.
	 */
	public function __construct(
		protected MultiResponseHandlerInterface          $multiResponseHandler,
		protected HTTPOptions|SettingsContainerInterface $options = new HTTPOptions,
		protected ResponseFactoryInterface               $responseFactory = new HTTPFactory,
		protected LoggerInterface                        $logger = new NullLogger,
	){
		$this->curl_multi = curl_multi_init();

		$curl_multi_options = ([
			CURLMOPT_PIPELINING  => CURLPIPE_MULTIPLEX,
			CURLMOPT_MAXCONNECTS => $this->options->window_size,
		] + $this->options->curl_multi_options);

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
	public function setLogger(LoggerInterface $logger):void{
		$this->logger = $logger;
	}

	/**
	 *
	 */
	public function close():void{

		if($this->curl_multi instanceof CMH){
			curl_multi_close($this->curl_multi);
		}

	}

	/**
	 *
	 */
	public function addRequest(RequestInterface $request):static{
		$this->requests[] = $request;

		return $this;
	}

	/**
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
	 * @phan-suppress PhanTypeInvalidThrowsIsInterface
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
			$status = curl_multi_exec($this->curl_multi, $active);

			if($active > 0){
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
		while($active > 0 && $status === CURLM_OK);

		return $this;
	}

	/**
	 *
	 */
	protected function createHandle(RequestInterface|null $request = null, int|null $id = null, int|null $retries = null):void{

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
			$this->options,
		);

		$curl = $handle
			->setID(($id ?? $this->handleCounter++))
			->setRetries($retries ?? 1)
			->init()
		;

		/** @phan-suppress-next-line PhanTypeMismatchArgumentNullableInternal */
		curl_multi_add_handle($this->curl_multi, $curl);

		$this->handles[(int)$curl] = $handle;

		if($this->options->sleep > 0){
			usleep($this->options->sleep);
		}

	}

}
