<?php
/**
 * Class CurlMultiHandle
 *
 * @created      03.11.2020
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2020 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Common;

use chillerlan\Settings\SettingsContainerInterface;
use Psr\Http\Message\{RequestInterface, ResponseInterface};

/**
 * Implements a cURL multi connection object
 */
class CurlMultiHandle extends CurlHandle{

	public function __construct(
		protected MultiResponseHandlerInterface $multiResponseHandler,
		RequestInterface                        $request,
		ResponseInterface                       $response,
		SettingsContainerInterface              $options,
	){
		parent::__construct($request, $response, $options);
	}

	/**
	 * a handle ID (counter), used in CurlMultiClient
	 */
	protected int|null $id = null;

	/**
	 * a retry counter, used in CurlMultiClient
	 */
	protected int $retries = 0;

	/**
	 *
	 */
	public function getID():int|null{
		return $this->id;
	}

	/**
	 *
	 */
	public function setID(int $id):static{
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
	public function setRetries(int $retries):static{
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
	public function handleResponse():RequestInterface|null{
		$info = curl_getinfo($this->curl);

		return $this->multiResponseHandler->handleResponse(
			$this->response,
			$this->request,
			$this->id,
			(is_array($info) ? $info : []),
		);
	}

}
