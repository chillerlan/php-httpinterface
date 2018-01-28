<?php
/**
 * Trait HTTPClientTrait
 *
 * @filesource   HTTPClientTrait.php
 * @created      28.01.2018
 * @package      chillerlan\HTTP
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

trait HTTPClientTrait{

	/**
	 * @var \chillerlan\HTTP\HTTPClientInterface
	 */
	private $http;

	/**
	 * @param \chillerlan\HTTP\HTTPClientInterface $http
	 *
	 * @return $this
	 */
	protected function setHTTPClient(HTTPClientInterface $http){
		$this->http = $http;

		return $this;
	}

	/**
	 * @param string      $url
	 * @param array|null  $params
	 * @param string|null $method
	 * @param null        $body
	 * @param array|null  $headers
	 *
	 * @return \chillerlan\HTTP\HTTPResponseInterface
	 */
	protected function httpRequest(string $url, array $params = null, string $method = null, $body = null, array $headers = null):HTTPResponseInterface{
		return $this->http->request($url, $params, $method, $body, $headers);
	}

	/**
	 * @param string     $url
	 * @param array|null $params
	 * @param array|null $headers
	 *
	 * @return \chillerlan\HTTP\HTTPResponseInterface
	 */
	protected function httpDELETE(string $url, array $params = null, array $headers = null):HTTPResponseInterface{
		return $this->http->request($url, $params, 'DELETE', null, $headers);
	}

	/**
	 * @param string     $url
	 * @param array|null $params
	 * @param array|null $headers
	 *
	 * @return \chillerlan\HTTP\HTTPResponseInterface
	 */
	protected function httpGET(string $url, array $params = null, array $headers = null):HTTPResponseInterface{
		return $this->http->request($url, $params, 'GET', null, $headers);
	}

	/**
	 * @param string     $url
	 * @param array|null $params
	 * @param null       $body
	 * @param array|null $headers
	 *
	 * @return \chillerlan\HTTP\HTTPResponseInterface
	 */
	protected function httpPATCH(string $url, array $params = null, $body = null, array $headers = null):HTTPResponseInterface{
		return $this->http->request($url, $params, 'PATCH', $body, $headers);
	}

	/**
	 * @param string     $url
	 * @param array|null $params
	 * @param null       $body
	 * @param array|null $headers
	 *
	 * @return \chillerlan\HTTP\HTTPResponseInterface
	 */
	protected function httpPOST(string $url, array $params = null, $body = null, array $headers = null):HTTPResponseInterface{
		return $this->http->request($url, $params, 'POST', $body, $headers);
	}

	/**
	 * @param string     $url
	 * @param array|null $params
	 * @param null       $body
	 * @param array|null $headers
	 *
	 * @return \chillerlan\HTTP\HTTPResponseInterface
	 */
	protected function httpPUT(string $url, array $params = null, $body = null, array $headers = null):HTTPResponseInterface{
		return $this->http->request($url, $params, 'PUT', $body, $headers);
	}

	/**
	 * @param array $headers
	 *
	 * @return array
	 */
	protected function normalizeRequestHeaders(array $headers):array{
		return $this->http->normalizeRequestHeaders($headers);
	}

	/**
	 * @param mixed     $params
	 * @param bool|null $booleans_as_string
	 *
	 * @return array
	 */
	protected function checkQueryParams($params, bool $booleans_as_string = null){

		if(is_array($params)){
			return $this->http->checkQueryParams($params, $booleans_as_string);
		}

		return $params;
	}

	/**
	 * @param array       $params
	 * @param bool|null   $urlencode
	 * @param string|null $delimiter
	 * @param string|null $enclosure
	 *
	 * @return string
	 */
	protected function httpBuildQuery(array $params, bool $urlencode = null, string $delimiter = null, string $enclosure = null):string {
		return $this->http->buildQuery($params, $urlencode, $delimiter, $enclosure);
	}

}
