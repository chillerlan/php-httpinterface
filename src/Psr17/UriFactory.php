<?php
/**
 * Class UriFactory
 *
 * @filesource   UriFactory.php
 * @created      27.08.2018
 * @package      chillerlan\HTTP\Psr17
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr17;

use chillerlan\HTTP\Psr7\Uri;
use Psr\Http\Message\{UriFactoryInterface, UriInterface};

final class UriFactory implements UriFactoryInterface{

	/**
	 * Create a new URI.
	 *
	 * @param string $uri
	 *
	 * @return \Psr\Http\Message\UriInterface|\chillerlan\HTTP\Psr7\Uri
	 *
	 * @throws \InvalidArgumentException If the given URI cannot be parsed.
	 */
	public function createUri(string $uri = ''):UriInterface{
		return new Uri($uri);
	}

	/**
	 * @see \parse_url()
	 *
	 * @param array $parts
	 *
	 * @return \Psr\Http\Message\UriInterface|\chillerlan\HTTP\Psr7\Uri
	 */
	public function createUriFromParts(array $parts):UriInterface{
		return Uri::fromParts($parts);
	}

	/**
	 * Get a Uri populated with values from $_SERVER.
	 *
	 * @return \Psr\Http\Message\UriInterface|\chillerlan\HTTP\Psr7\Uri
	 */
	public function createUriFromGlobals():UriInterface{
		$parts    = [];
		$hasPort  = false;
		$hasQuery = false;

		$parts['scheme'] = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';

		if(isset($_SERVER['HTTP_HOST'])){
			$hostHeaderParts = explode(':', $_SERVER['HTTP_HOST']);
			$parts['host'] = $hostHeaderParts[0];

			if(isset($hostHeaderParts[1])){
				$hasPort       = true;
				$parts['port'] = $hostHeaderParts[1];
			}
		}
		elseif(isset($_SERVER['SERVER_NAME'])){
			$parts['host'] = $_SERVER['SERVER_NAME'];
		}
		elseif(isset($_SERVER['SERVER_ADDR'])){
			$parts['host'] = $_SERVER['SERVER_ADDR'];
		}

		if(!$hasPort && isset($_SERVER['SERVER_PORT'])){
			$parts['port'] = $_SERVER['SERVER_PORT'];
		}

		if(isset($_SERVER['REQUEST_URI'])){
			$requestUriParts = explode('?', $_SERVER['REQUEST_URI']);
			$parts['path']   = $requestUriParts[0];

			if(isset($requestUriParts[1])){
				$hasQuery       = true;
				$parts['query'] = $requestUriParts[1];
			}
		}

		if(!$hasQuery && isset($_SERVER['QUERY_STRING'])){
			$parts['query'] = $_SERVER['QUERY_STRING'];
		}

		return Uri::fromParts($parts);
	}

	/**
	 * @param mixed $data
	 *
	 * @return mixed
	 */
	public function rawurlencode($data){

		if(is_array($data)){
			return array_map([$this, 'rawurlencode'], $data);
		}
		elseif(is_scalar($data)){
			return rawurlencode($data);
		}

		return $data; // @codeCoverageIgnore
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
	public function buildQuery(array $params, bool $urlencode = null, string $delimiter = null, string $enclosure = null):string {

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

	/**
	 * @param array     $params
	 * @param bool|null $booleans_as_string - converts booleans to "true"/"false" strings if set to true, "0"/"1" otherwise.
	 *
	 * @return array
	 */
	public function cleanQueryParams(array $params, bool $booleans_as_string = null):array{

		foreach($params as $key => $value){

			if(is_bool($value)){
				$params[$key] = $booleans_as_string === true
					? ($value ? 'true' : 'false')
					: (string)(int)$value;
			}
			elseif($value === null || (!is_numeric($value) && empty($value))){
				unset($params[$key]);
			}

		}

		return $params;
	}


}
