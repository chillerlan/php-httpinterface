<?php
/**
 * Interface HTTPClientInterface
 *
 * @filesource   HTTPClientInterface.php
 * @created      09.07.2017
 * @package      chillerlan\HTTP
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

interface HTTPClientInterface{

	/**
	 * @param string      $url
	 * @param array|null  $params
	 * @param string|null $method
	 * @param mixed|null  $body
	 * @param array|null  $headers
	 *
	 * @return \chillerlan\HTTP\HTTPResponseInterface
	 * @throws \chillerlan\HTTP\HTTPClientException
	 */
	public function request(string $url, array $params = null, string $method = null, $body = null, array $headers = null):HTTPResponseInterface;

	/**
	 * @param array $headers
	 *
	 * @return array
	 */
	public function normalizeRequestHeaders(array $headers):array;

	/**
	 * @param mixed $data
	 *
	 * @return mixed
	 */
	public function rawurlencode($data);

	/**
	 * @param array       $params
	 * @param bool|null   $urlencode
	 * @param string|null $delimiter
	 * @param string|null $enclosure
	 *
	 * @return string
	 */
	public function buildQuery(array $params, bool $urlencode = null, string $delimiter = null, string $enclosure = null):string;

	/**
	 * @param array     $params
	 * @param bool|null $booleans_as_string - converts booleans to "true"/"false" strings if set to true, "0"/"1" otherwise.
	 *
	 * @return array
	 */
	public function checkQueryParams(array $params, bool $booleans_as_string = null):array;

}
