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
	 * @param $params
	 *
	 * @return array
	 */
	protected function checkParams($params){

		if(is_array($params)){

			foreach($params as $key => $value){

				if(is_bool($value)){
					$params[$key] = (string)(int)$value;
				}
				elseif(is_null($value) || empty($value)){
					unset($params[$key]);
				}

			}

		}

		return $params;
	}

	/**
	 * @param $data
	 *
	 * @return array|string
	 */
	protected function rawurlencode($data){

		if(is_array($data)){
			return array_map([$this, 'rawurlencode'], $data);
		}
		elseif(is_scalar($data)){
			return rawurlencode($data);
		}

		return $data;
	}

	/**
	 * from https://github.com/abraham/twitteroauth/blob/master/src/Util.php
	 *
	 * @param array  $params
	 * @param bool   $urlencode
	 * @param string $delimiter
	 * @param string $enclosure
	 *
	 * @return string
	 */
	public function buildHttpQuery(array $params, bool $urlencode = null, string $delimiter = null, string $enclosure = null):string {

		if(empty($params)) {
			return '';
		}

		// urlencode both keys and values
		if($urlencode ?? true){
			$params = array_combine(
				$this->rawurlencode(array_keys($params)),
				$this->rawurlencode(array_values($params))
			);
		}

		// Parameters are sorted by name, using lexicographical byte value ordering.
		// Ref: Spec: 9.1.1 (1)
		uksort($params, 'strcmp');

		$pairs     = [];
		$enclosure = $enclosure ?? '';

		foreach($params as $parameter => $value){

			if(is_array($value)) {
				// If two or more parameters share the same name, they are sorted by their value
				// Ref: Spec: 9.1.1 (1)
				// June 12th, 2010 - changed to sort because of issue 164 by hidetaka
				sort($value, SORT_STRING);

				foreach ($value as $duplicateValue) {
					$pairs[] = $parameter.'='.$enclosure.$duplicateValue.$enclosure;
				}

			}
			else{
				$pairs[] = $parameter.'='.$enclosure.$value.$enclosure;
			}

		}

		// For each parameter, the name is separated from the corresponding value by an '=' character (ASCII code 61)
		// Each name-value pair is separated by an '&' character (ASCII code 38)
		return implode($delimiter ?? '&', $pairs);
	}


}
