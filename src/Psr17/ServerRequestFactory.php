<?php
/**
 * Class ServerRequestFactory
 *
 * @created      27.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr17;

use chillerlan\HTTP\Psr7\ServerRequest;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\{ServerRequestFactoryInterface, ServerRequestInterface};

class ServerRequestFactory implements ServerRequestFactoryInterface, RequestMethodInterface{

	/**
	 * @inheritDoc
	 */
	public function createServerRequest(string $method, $uri, array $serverParams = []):ServerRequestInterface{
		return new ServerRequest($method, $uri, $serverParams);
	}

}
