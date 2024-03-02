<?php
/**
 * Class RequestException
 *
 * @created      10.09.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTP\Psr18;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Throwable;

/**
 * @codeCoverageIgnore
 */
class RequestException extends ClientException implements RequestExceptionInterface{

	/**
	 *
	 */
	public function __construct(
		string                     $message,
		protected RequestInterface $request,
		Throwable|null             $previous = null
	){
		parent::__construct($message, 0, $previous);
	}

	/**
	 * @inheritDoc
	 */
	public function getRequest():RequestInterface{
		return $this->request;
	}

}
