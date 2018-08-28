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
	 * Create a new server request.
	 *
	 * Note that server-params are taken precisely as given - no parsing/processing
	 * of the given values is performed, and, in particular, no attempt is made to
	 * determine the HTTP method or URI, which must be provided explicitly.
	 *
	 * @param string                                $method       The HTTP method associated with the request.
	 * @param \Psr\Http\Message\UriInterface|string $uri          The URI associated with the request. If
	 *                                                            the value is a string, the factory MUST
	 *                                                            create a UriInterface instance based on it.
	 * @param array                                 $serverParams Array of SAPI parameters with which to seed
	 *                                                            the generated request instance.
	 *
	 * @return \Psr\Http\Message\ServerRequestInterface|\chillerlan\HTTP\Psr7\ServerRequest
	 */
	public function createServerRequest(string $method, $uri, array $serverParams = []):ServerRequestInterface{
		return new ServerRequest($method, $uri, null, null, null, $serverParams);
	}

	/**
	 * Return a ServerRequest populated with superglobals:
	 * $_GET
	 * $_POST
	 * $_COOKIE
	 * $_FILES
	 * $_SERVER
	 *
	 * @return \Psr\Http\Message\ServerRequestInterface|\chillerlan\HTTP\Psr7\ServerRequest
	 */
	public function createServerRequestFromGlobals():ServerRequestInterface{
		return create_server_request_from_globals();
	}

}
