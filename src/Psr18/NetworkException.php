<?php
/**
 * Class NetworkException
 *
 * @created      10.09.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr18;

use Throwable;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * @codeCoverageIgnore
 */
class NetworkException extends ClientException implements NetworkExceptionInterface{

	protected RequestInterface $request;

	/**
	 *
	 */
	public function __construct(string $message, RequestInterface $request, Throwable $previous = null){
		$this->request = $request;

		parent::__construct($message, 0, $previous);
	}

	/**
	 * @inheritDoc
	 */
	public function getRequest():RequestInterface{
		return $this->request;
	}

}
