<?php
/**
 * Class Query
 *
 * @created      27.03.2021
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2021 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use function array_merge, explode, implode, is_array, is_bool, is_string, parse_url, rawurldecode, sort, str_replace, uksort;
use const PHP_QUERY_RFC1738, PHP_QUERY_RFC3986, PHP_URL_QUERY, SORT_STRING;

/**
 *
 */
final class Query{

	public const BOOLEANS_AS_BOOL       = 0;
	public const BOOLEANS_AS_INT        = 1;
	public const BOOLEANS_AS_STRING     = 2;
	public const BOOLEANS_AS_INT_STRING = 3;

	public const NO_ENCODING = -1;

	/**
	 * @param iterable  $params
	 * @param int|null  $bool_cast    converts booleans to a type determined like following:
	 *                                BOOLEANS_AS_BOOL      : unchanged boolean value (default)
	 *                                BOOLEANS_AS_INT       : integer values 0 or 1
	 *                                BOOLEANS_AS_STRING    : "true"/"false" strings
	 *                                BOOLEANS_AS_INT_STRING: "0"/"1"
	 *
	 * @param bool|null $remove_empty remove empty and NULL values (default: true)
	 *
	 * @return array
	 */
	public static function cleanParams(iterable $params, int $bool_cast = null, bool $remove_empty = null):array{
		$bool_cast    ??= self::BOOLEANS_AS_BOOL;
		$remove_empty ??= true;

		$cleaned = [];

		foreach($params as $key => $value){

			if(is_iterable($value)){
				// recursion
				$cleaned[$key] = call_user_func_array(__METHOD__, [$value, $bool_cast, $remove_empty]);
			}
			elseif(is_bool($value)){

				if($bool_cast === self::BOOLEANS_AS_BOOL){
					$cleaned[$key] = $value;
				}
				elseif($bool_cast === self::BOOLEANS_AS_INT){
					$cleaned[$key] = (int)$value;
				}
				elseif($bool_cast === self::BOOLEANS_AS_STRING){
					$cleaned[$key] = $value ? 'true' : 'false';
				}
				elseif($bool_cast === self::BOOLEANS_AS_INT_STRING){
					$cleaned[$key] = (string)(int)$value;
				}

			}
			elseif(is_string($value)){
				$value = trim($value);

				if($remove_empty && empty($value)){
					continue;
				}

				$cleaned[$key] = $value;
			}
			else{

				if($remove_empty && ($value === null || (!is_numeric($value) && empty($value)))){
					continue;
				}

				$cleaned[$key] = $value;
			}
		}

		return $cleaned;
	}

	/**
	 * Build a query string from an array of key value pairs.
	 *
	 * Valid values for $encoding are PHP_QUERY_RFC3986 (default) and PHP_QUERY_RFC1738,
	 * any other integer value will be interpreted as "no encoding".
	 *
	 * @link https://github.com/abraham/twitteroauth/blob/57108b31f208d0066ab90a23257cdd7bb974c67d/src/Util.php#L84-L122
	 * @link https://github.com/guzzle/psr7/blob/c0dcda9f54d145bd4d062a6d15f54931a67732f9/src/Query.php#L59-L113
	 */
	public static function build(array $params, int $encoding = null, string $delimiter = null, string $enclosure = null):string{

		if(empty($params)){
			return '';
		}

		$encoding  ??= PHP_QUERY_RFC3986;
		$enclosure ??= '';
		$delimiter ??= '&';

		if($encoding === PHP_QUERY_RFC3986){
			$encode = 'rawurlencode';
		}
		elseif($encoding === PHP_QUERY_RFC1738){
			$encode = 'urlencode';
		}
		else{
			$encode = fn(string $str):string => $str;
		}

		$pair = function(string $key, $value) use ($encode, $enclosure):string{

			if($value === null){
				return $key;
			}

			if(is_bool($value)){
				$value = (int)$value;
			}

			// For each parameter, the name is separated from the corresponding value by an '=' character (ASCII code 61)
			return $key.'='.$enclosure.$encode((string)$value).$enclosure;
		};

		// Parameters are sorted by name, using lexicographical byte value ordering.
		uksort($params, 'strcmp');

		$pairs = [];

		foreach($params as $parameter => $value){
			$parameter = $encode((string)$parameter);

			if(is_array($value)){
				// If two or more parameters share the same name, they are sorted by their value
				sort($value, SORT_STRING);

				foreach($value as $duplicateValue){
					$pairs[] = $pair($parameter, $duplicateValue);
				}

			}
			else{
				$pairs[] = $pair($parameter, $value);
			}

		}

		// Each name-value pair is separated by an '&' character (ASCII code 38)
		return implode($delimiter, $pairs);
	}

	/**
	 * merges additional query parameters into an existing query string
	 *
	 * @param string $uri
	 * @param array  $query
	 *
	 * @return string
	 */
	public static function merge(string $uri, array $query):string{
		$parsedquery = self::parse(parse_url($uri, PHP_URL_QUERY) ?: '');
		$requestURI  = explode('?', $uri)[0];
		$params      = array_merge($parsedquery, $query);

		if(!empty($params)){
			$requestURI .= '?'.Query::build($params);
		}

		return $requestURI;
	}

	/**
	 * Parse a query string into an associative array.
	 *
	 * @link https://github.com/guzzle/psr7/blob/c0dcda9f54d145bd4d062a6d15f54931a67732f9/src/Query.php#L9-L57
	 */
	public static function parse(string $querystring, int $urlEncoding = null):array{

		if($querystring === ''){
			return [];
		}

		if($urlEncoding === self::NO_ENCODING){
			$decode = fn(string $str):string => $str;
		}
		elseif($urlEncoding === PHP_QUERY_RFC3986){
			$decode = 'rawurldecode';
		}
		elseif($urlEncoding === PHP_QUERY_RFC1738){
			$decode = 'urldecode';
		}
		else{
			$decode = fn(string $value):string => rawurldecode(str_replace('+', ' ', $value));
		}

		$result = [];

		foreach(explode('&', $querystring) as $pair){
			$parts = explode('=', $pair, 2);
			$key   = $decode($parts[0]);
			$value = isset($parts[1]) ? $decode($parts[1]) : null;

			if(!isset($result[$key])){
				$result[$key] = $value;
			}
			else{

				if(!is_array($result[$key])){
					$result[$key] = [$result[$key]];
				}

				$result[$key][] = $value;
			}
		}

		return $result;
	}

}
