<?php
/**
 * Class RequestFactory
 *
 * @created      27.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr17;

use chillerlan\HTTP\Psr7\Request;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\{RequestFactoryInterface, RequestInterface};

/**
 *
 */
class RequestFactory implements RequestFactoryInterface, RequestMethodInterface{

	/**
	 * @inheritDoc
	 */
	public function createRequest(string $method, $uri):RequestInterface{
		return new Request($method, $uri);
	}

}
