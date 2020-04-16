<?php
/**
 * Class CurlMultiClient
 *
 * @filesource   CurlMultiClient.php
 * @created      30.08.2018
 * @package      chillerlan\HTTP\CurlUtils
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\CurlUtils;

use chillerlan\HTTP\{HTTPOptions, Psr17\ResponseFactory, Psr18\ClientException};
use chillerlan\Settings\SettingsContainerInterface;
use Psr\Http\Message\{RequestInterface, ResponseFactoryInterface};
use Psr\Log\{LoggerAwareInterface, LoggerAwareTrait, LoggerInterface, NullLogger};

use function array_shift, curl_close, curl_getinfo, curl_multi_add_handle, curl_multi_close, curl_multi_exec,
	curl_multi_info_read, curl_multi_init, curl_multi_remove_handle, curl_multi_select, curl_multi_setopt,
	is_array, is_resource, usleep;

use const CURLM_OK, CURLMOPT_MAXCONNECTS, CURLMOPT_PIPELINING, CURLPIPE_MULTIPLEX;

class CurlMultiClient implements LoggerAwareInterface{
	use LoggerAwareTrait;

	/** @var \chillerlan\Settings\SettingsContainerInterface|\chillerlan\HTTP\HTTPOptions */
	protected SettingsContainerInterface $options;

	protected ResponseFactoryInterface $responseFactory;

	protected ?MultiResponseHandlerInterface $multiResponseHandler = null;

	/**
	 * the curl_multi master handle
	 *
	 * @var resource
	 */
	protected $curl_multi;

	/**
	 * An array of RequestInterface to run
	 *
	 * @var \Psr\Http\Message\RequestInterface[]
	 */
	protected array $requests = [];

	/**
	 * the stack of running handles
	 *
	 * @var \chillerlan\HTTP\CurlUtils\CurlHandle[]
	 */
	protected array $handles = [];

	/**
	 * @var int
	 */
	protected int $handleCounter = 0;

	/**
	 * CurlMultiClient constructor.
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
		$this->curl_multi      = curl_multi_init();

		$curl_multi_options = [
			CURLMOPT_PIPELINING  => CURLPIPE_MULTIPLEX,
			CURLMOPT_MAXCONNECTS => $this->options->windowSize,
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
	 * @param \chillerlan\HTTP\CurlUtils\MultiResponseHandlerInterface $handler
	 *
	 * @return \chillerlan\HTTP\CurlUtils\CurlMultiClient
	 */
	public function setMultiResponseHandler(MultiResponseHandlerInterface $handler):CurlMultiClient{
		$this->multiResponseHandler = $handler;

		return $this;
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
	 * @throws \chillerlan\HTTP\Psr18\ClientException
	 */
	public function process():CurlMultiClient{

		if(empty($this->requests)){
			throw new ClientException('request stack is empty');
		}

		if(!$this->multiResponseHandler instanceof MultiResponseHandlerInterface){
			throw new ClientException('no response handler set');
		}

		// shoot out the first batch of requests
		for($i = 0; $i < $this->options->windowSize; $i++){
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
				$info   = curl_getinfo($handle->curl);
				$result = $this->multiResponseHandler->handleResponse($handle->response, $handle->request, $handle->id, (is_array($info) ? $info : []));

				curl_multi_remove_handle($this->curl_multi, $state['handle']);
				curl_close($state['handle']);
				unset($this->handles[$id]);

				if($result instanceof RequestInterface && $handle->retries < $this->options->retries){
					$this->createHandle($result, $handle->id, ++$handle->retries);

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
	protected function createHandle(RequestInterface $request = null, int $id = null, int $retries = null):void{

		if($request === null){

			if(empty($this->requests)){
				return;
			}

			$request = array_shift($this->requests);
		}

		$handle          = new $this->options->curlHandle($request, $this->responseFactory->createResponse(), $this->options);
		$handle->id      = $id ?? $this->handleCounter++;
		$handle->retries = $retries ?? 1;

		$handle->init();
		curl_multi_add_handle($this->curl_multi, $handle->curl);

		$this->handles[(int)$handle->curl] = $handle;

		if($this->options->sleep > 0){
			usleep($this->options->sleep);
		}

	}

}
