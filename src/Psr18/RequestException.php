<?php
/**
 * Class RequestException
 *
 * @filesource   RequestException.php
 * @created      10.09.2018
 * @package      chillerlan\HTTP
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr18;

use Exception;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;

class RequestException extends ClientException implements RequestExceptionInterface{

	/**
	 * @var \Psr\Http\Message\RequestInterface
	 */
	private $request;

	/**
	 * @param string                             $message
	 * @param \Psr\Http\Message\RequestInterface $request
	 * @param \Exception|null                    $previous
	 */
	public function __construct(string $message, RequestInterface $request, Exception $previous = null){
		$this->request = $request;

		parent::__construct($message, 0, $previous);
	}

	/**
	 * Returns the request.
	 *
	 * The request object MAY be a different object from the one passed to ClientInterface::sendRequest()
	 *
	 * @return \Psr\Http\Message\RequestInterface
	 */
	public function getRequest():RequestInterface{
		return $this->request;
	}

}
