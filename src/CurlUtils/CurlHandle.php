<?php
/**
 * Class CurlHandle
 *
 * @filesource   CurlHandle.php
 * @created      30.08.2018
 * @package      chillerlan\HTTP\CurlUtils
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\CurlUtils;

use chillerlan\Settings\SettingsContainerInterface;
use Psr\Http\Message\{RequestInterface, ResponseInterface};

use function array_key_exists, count, curl_close, curl_init, curl_reset, curl_setopt_array,
	explode, in_array, is_resource, strlen, strtolower, strtoupper, substr, trim;

use const CURLOPT_CAINFO, CURLOPT_CONNECTTIMEOUT, CURLOPT_CUSTOMREQUEST, CURLOPT_FOLLOWLOCATION, CURLOPT_HEADER,
	CURLOPT_HEADERFUNCTION, CURLOPT_HTTP_VERSION, CURLOPT_HTTPHEADER, CURLOPT_INFILESIZE, CURLOPT_NOBODY,
	CURLOPT_POSTFIELDS, CURLOPT_PROGRESSFUNCTION, CURLOPT_PROTOCOLS, CURLOPT_READFUNCTION, CURLOPT_RETURNTRANSFER,
	CURLOPT_SSL_VERIFYHOST, CURLOPT_SSL_VERIFYPEER, CURLOPT_TIMEOUT, CURLOPT_UPLOAD, CURLOPT_URL, CURLOPT_USERAGENT,
	CURLOPT_USERPWD, CURLOPT_WRITEFUNCTION, CURLPROTO_HTTP, CURLPROTO_HTTPS, CURL_HTTP_VERSION_2TLS;

class CurlHandle implements CurlHandleInterface{

	/**
	 * The cURL handle
	 *
	 * @var resource
	 */
	public $curl;

	/**
	 * a handle ID (counter), used in CurlMultiClient
	 *
	 * @var int
	 */
	public $id;

	/**
	 * a retry counter, used in CurlMultiClient
	 *
	 * @var int
	 */
	public $retries;

	/**
	 * @var \Psr\Http\Message\RequestInterface
	 */
	public $request;

	/**
	 * @var \Psr\Http\Message\ResponseInterface
	 */
	public $response;

	/**
	 * @var \chillerlan\HTTP\HTTPOptions
	 */
	protected $options;

	/**
	 * CurlHandle constructor.
	 *
	 * @param \Psr\Http\Message\RequestInterface              $request
	 * @param \Psr\Http\Message\ResponseInterface             $response
	 * @param \chillerlan\Settings\SettingsContainerInterface $options
	 */
	public function __construct(RequestInterface $request, ResponseInterface $response, SettingsContainerInterface $options){
		$this->request  = $request;
		$this->response = $response;
		$this->options  = $options;
		$this->curl     = curl_init();
	}

	/**
	 * close an existing cURL handle on exit
	 */
	public function __destruct(){
		$this->close();
	}

	/**
	 * @return void
	 */
	public function close():CurlHandleInterface{

		if(is_resource($this->curl)){
			curl_close($this->curl);
		}

		return $this;
	}

	/**
	 * @return void
	 */
	public function reset():CurlHandleInterface{

		if(is_resource($this->curl)){

			curl_setopt_array($this->curl, [
				CURLOPT_HEADERFUNCTION   => null,
				CURLOPT_READFUNCTION     => null,
				CURLOPT_WRITEFUNCTION    => null,
				CURLOPT_PROGRESSFUNCTION => null,
			]);

			curl_reset($this->curl);
		}

		return $this;
	}

	/**
	 * @return array
	 */
	protected function initCurlOptions():array{
		return [
			CURLOPT_HEADER         => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_URL            => (string)$this->request->getUri()->withFragment(''),
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2TLS,
			CURLOPT_USERAGENT      => $this->options->user_agent,
			CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_CAINFO         => $this->options->ca_info,
			CURLOPT_TIMEOUT        => $this->options->timeout,
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_WRITEFUNCTION  => [$this, 'writefunction'],
			CURLOPT_HEADERFUNCTION => [$this, 'headerfunction'],
		];
	}

	/**
	 * @param array $options
	 *
	 * @return void
	 */
	protected function setBodyOptions(array &$options):void{
		$body     = $this->request->getBody();
		$bodySize = $body->getSize();

		if($bodySize === 0){
			return;
		}

		if($body->isSeekable()){
			$body->rewind();
		}

		// Message has non empty body.
		if($bodySize === null || $bodySize > 1 << 20){
			// Avoid full loading large or unknown size body into memory
			$options[CURLOPT_UPLOAD] = true;

			if($bodySize !== null){
				$options[CURLOPT_INFILESIZE] = $bodySize;
			}

			$options[CURLOPT_READFUNCTION] = [$this, 'readfunction'];
		}
		// Small body can be loaded into memory
		else{
			$options[CURLOPT_POSTFIELDS] = (string)$body;
		}

	}

	/**
	 * @param array $options
	 *
	 * @return array
	 */
	protected function initCurlHeaders(array $options):array{
		$headers = [];

		foreach($this->request->getHeaders() as $name => $values){
			$header = strtolower($name);

			// curl-client does not support "Expect-Continue", so dropping "expect" headers
			if($header === 'expect'){
				continue;
			}

			if($header === 'content-length'){

				// Small body content length can be calculated here.
				if(array_key_exists(CURLOPT_POSTFIELDS, $options)){
					$values = [strlen($options[CURLOPT_POSTFIELDS])];
				}
				// Else if there is no body, forcing "Content-length" to 0
				elseif(!array_key_exists(CURLOPT_READFUNCTION, $options)){
					$values = ['0'];
				}

			}

			foreach($values as $value){
				$value = (string)$value;

				// cURL requires a special format for empty headers.
				// See https://github.com/guzzle/guzzle/issues/1882 for more details.
				$headers[] = $value === '' ? $name.';' : $name.': '.$value;
			}

		}

		return $headers;
	}

	/**
	 * @return \chillerlan\HTTP\CurlUtils\CurlHandleInterface
	 */
	public function init():CurlHandleInterface{
		$options  = $this->initCurlOptions();
		$userinfo = $this->request->getUri()->getUserInfo();
		$method   = $this->request->getMethod();

		if(!empty($userinfo)){
			$options[CURLOPT_USERPWD] = $userinfo;
		}

		/*
		 * Some HTTP methods cannot have payload:
		 *
		 * - GET   — cURL will automatically change the method to PUT or POST
		 *           if we set CURLOPT_UPLOAD or CURLOPT_POSTFIELDS.
		 * - HEAD  — cURL treats HEAD as a GET request with same restrictions.
		 * - TRACE — According to RFC7231: a client MUST NOT send a message body in a TRACE request.
		 */

		if(in_array($method, ['DELETE', 'PATCH', 'POST', 'PUT'], true)){
			$this->setBodyOptions($options);
		}

		// This will set HTTP method to "HEAD".
		if($method === 'HEAD'){
			$options[CURLOPT_NOBODY] = true;
		}

		// GET is a default method. Other methods should be specified explicitly.
		if($method !== 'GET'){
			$options[CURLOPT_CUSTOMREQUEST] = $method;
		}

		// overwrite the default values with $curl_options
		foreach($this->options->curl_options as $k => $v){
			// skip some options that are only set automatically
			if(in_array($k, [CURLOPT_HTTPHEADER, CURLOPT_CUSTOMREQUEST, CURLOPT_NOBODY], true)){
				continue;
			}

			$options[$k] = $v;
		}

		$options[CURLOPT_HTTPHEADER] = $this->initCurlHeaders($options);

		// If the Expect header is not present, prevent curl from adding it
		if(!$this->request->hasHeader('Expect')){
			$options[CURLOPT_HTTPHEADER][] = 'Expect:';
		}

		// cURL sometimes adds a content-type by default. Prevent this.
		if(!$this->request->hasHeader('Content-Type')){
			$options[CURLOPT_HTTPHEADER][] = 'Content-Type:';
		}

		curl_setopt_array($this->curl, $options);

		return $this;
	}

	/**
	 * @param resource $curl
	 * @param resource $stream
	 * @param int      $length
	 *
	 * @return string
	 */
	public function readfunction($curl, $stream, int $length):string{
		return $this->request->getBody()->read($length);
	}

	/**
	 * @param resource $curl
	 * @param string   $data
	 *
	 * @return int
	 */
	public function writefunction($curl, string $data):int{
		return $this->response->getBody()->write($data);
	}

	/**
	 * @param resource $curl
	 * @param string   $line
	 *
	 * @return int
	 */

	public function headerfunction($curl, string $line):int{
		$str    = trim($line);
		$header = explode(':', $str, 2);

		if(count($header) === 2){
			$this->response = $this->response
				->withAddedHeader(trim($header[0]), trim($header[1]));
		}
		elseif(substr(strtoupper($str), 0, 5) === 'HTTP/'){
			$status = explode(' ', $str, 3);
			$reason = count($status) > 2 ? trim($status[2]) : '';

			$this->response = $this->response
				->withStatus((int)$status[1], $reason)
				->withProtocolVersion(substr($status[0], 5))
			;
		}

		return strlen($line);
	}

}
