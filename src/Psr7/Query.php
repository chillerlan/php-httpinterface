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

use function array_combine;
use function array_keys;
use function array_merge;
use function array_values;
use function explode;
use function implode;
use function is_array;
use function parse_str;
use function parse_url;
use function sort;
use function uksort;
use const PHP_URL_QUERY;
use const SORT_STRING;

/**
 *
 */
class Query{

	public const BOOLEANS_AS_BOOL       = 0;
	public const BOOLEANS_AS_INT        = 1;
	public const BOOLEANS_AS_STRING     = 2;
	public const BOOLEANS_AS_INT_STRING = 3;

	/**
	 * @param iterable  $params
	 * @param int|null  $bool_cast    converts booleans to a type determined like following:
	 *                                BOOLEANS_AS_BOOL      : unchanged boolean value (default)
	 *                                BOOLEANS_AS_INT       : integer values 0 or 1
	 *                                BOOLEANS_AS_STRING    : "true"/"false" strings
	 *                                BOOLEANS_AS_INT_STRING: "0"/"1"
	 *
	 * @param bool|null $remove_empty remove empty and NULL values
	 *
	 * @return array
	 */
	public static function cleanParams(iterable $params, int $bool_cast = null, bool $remove_empty = null):array{
		$p            = [];
		$bool_cast    = $bool_cast ?? self::BOOLEANS_AS_BOOL;
		$remove_empty = $remove_empty ?? true;

		foreach($params as $key => $value){

			if(is_bool($value)){

				if($bool_cast === self::BOOLEANS_AS_BOOL){
					$p[$key] = $value;
				}
				elseif($bool_cast === self::BOOLEANS_AS_INT){
					$p[$key] = (int)$value;
				}
				elseif($bool_cast === self::BOOLEANS_AS_STRING){
					$p[$key] = $value ? 'true' : 'false';
				}
				elseif($bool_cast === self::BOOLEANS_AS_INT_STRING){
					$p[$key] = (string)(int)$value;
				}

			}
			elseif(is_iterable($value)){
				$p[$key] = call_user_func_array(__METHOD__, [$value, $bool_cast, $remove_empty]);
			}
			elseif($remove_empty === true && ($value === null || (!is_numeric($value) && empty($value)))){
				continue;
			}
			else{
				$p[$key] = $value;
			}
		}

		return $p;
	}

	/**
	 * from https://github.com/abraham/twitteroauth/blob/master/src/Util.php
	 */
	public static function build(array $params, bool $urlencode = null, string $delimiter = null, string $enclosure = null):string{

		if(empty($params)){
			return '';
		}

		// urlencode both keys and values
		if($urlencode ?? true){
			$params = array_combine(
				r_rawurlencode(array_keys($params)),
				r_rawurlencode(array_values($params))
			);
		}

		// Parameters are sorted by name, using lexicographical byte value ordering.
		// Ref: Spec: 9.1.1 (1)
		uksort($params, 'strcmp');

		$pairs     = [];
		$enclosure = $enclosure ?? '';

		foreach($params as $parameter => $value){

			if(is_array($value)){
				// If two or more parameters share the same name, they are sorted by their value
				// Ref: Spec: 9.1.1 (1)
				// June 12th, 2010 - changed to sort because of issue 164 by hidetaka
				sort($value, SORT_STRING);

				foreach($value as $duplicateValue){
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
	 * @todo placeholder/WIP
	 */
	public static function parse(string $querystring):array{
		parse_str($querystring, $parsedquery);

		return $parsedquery;
	}

}
