<?php
/**
 * Class ServerRequestFactory
 *
 * @filesource   ServerRequestFactory.php
 * @created      27.08.2018
 * @package      chillerlan\HTTP\Psr17
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr17;

use chillerlan\HTTP\Psr7\ServerRequest;
use Psr\Http\Message\{ServerRequestFactoryInterface, ServerRequestInterface};

final class ServerRequestFactory extends RequestFactory implements ServerRequestFactoryInterface{

	/**
	 * @inheritDoc
	 */
	public function createServerRequest(string $method, $uri, array $serverParams = []):ServerRequestInterface{
		return new ServerRequest($method, $uri, null, null, null, $serverParams);
	}

}
