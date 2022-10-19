<?php
/**
 * Class CurlMultiHandle
 *
 * @created      03.11.2020
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2020 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\CurlUtils;

use chillerlan\Settings\SettingsContainerInterface;
use Psr\Http\Message\{RequestInterface, ResponseInterface};

class CurlMultiHandle extends CurlHandle{

	private MultiResponseHandlerInterface $multiResponseHandler;

	public function __construct(
		MultiResponseHandlerInterface $multiResponseHandler,
		RequestInterface $request,
		ResponseInterface $response,
		SettingsContainerInterface $options
	){
		parent::__construct($request, $response, $options);

		$this->multiResponseHandler = $multiResponseHandler;
	}

	/**
	 * a handle ID (counter), used in CurlMultiClient
	 */
	private ?int $id = null;

	/**
	 * a retry counter, used in CurlMultiClient
	 */
	private int $retries = 0;

	/**
	 *
	 */
	public function getID():?int{
		return $this->id;
	}

	/**
	 *
	 */
	public function setID(int $id):CurlMultiHandle{
		$this->id = $id;

		return $this;
	}

	/**
	 *
	 */
	public function getRetries():int{
		return $this->retries;
	}

	/**
	 *
	 */
	public function setRetries(int $retries):CurlMultiHandle{
		$this->retries = $retries;

		return $this;
	}

	/**
	 *
	 */
	public function addRetry():int{
		return ++$this->retries;
	}

	/**
	 *
	 */
	public function handleResponse():?RequestInterface{
		$info = curl_getinfo($this->curl);

		return $this->multiResponseHandler->handleResponse(
			$this->response,
			$this->request,
			$this->id,
			(is_array($info) ? $info : [])
		);
	}
}
