<?php
/**
 * Interface ApiClientInterface
 *
 * @filesource   ApiClientInterface.php
 * @created      01.09.2018
 * @package      chillerlan\HTTP\MagicAPI
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\MagicAPI;

use Psr\Http\Message\ResponseInterface;

interface ApiClientInterface{

	/**
	 * @param string $endpointMap
	 *
	 * @return \chillerlan\HTTP\MagicAPI\ApiClientInterface
	 * @throws \chillerlan\HTTP\MagicAPI\ApiClientException
	 */
	public function loadEndpoints(string $endpointMap):ApiClientInterface;

	/**
	 * @param string $name
	 * @param array  $arguments
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 *
	 */
	public function __call(string $name, array $arguments):ResponseInterface;

}
