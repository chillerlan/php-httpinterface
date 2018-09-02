<?php
/**
 * Interface HTTPClientInterface
 *
 * @filesource   HTTPClientInterface.php
 * @created      27.08.2018
 * @package      chillerlan\HTTP
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2018 Smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

use Fig\Http\Message\RequestMethodInterface;
use Http\Client\HttpClient;
use Psr\Http\Message\ResponseInterface;

/**
 * ...waiting for PSR-18, going with HTTPlug for now
 *
 * @link https://github.com/php-fig/fig-standards/tree/master/proposed/http-client/
 */
interface HTTPClientInterface extends HttpClient, RequestMethodInterface{

	/**
	 * @param string      $uri
	 * @param string|null $method
	 * @param array|null  $query
	 * @param mixed|null  $body
	 * @param array|null  $headers
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 *
	 * @throws \Http\Client\Exception If an error happens during processing the request.
	 * @throws \Exception             If processing the request is impossible (eg. bad configuration).
	 */
	public function request(string $uri, string $method = null, array $query = null, $body = null, array $headers = null):ResponseInterface;

}
