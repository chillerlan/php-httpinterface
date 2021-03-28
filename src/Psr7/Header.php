<?php
/**
 * Class Header
 *
 * @created      28.03.2021
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2021 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use function array_keys;
use function array_map;
use function array_values;
use function count;
use function explode;
use function implode;
use function is_array;
use function is_numeric;
use function is_string;
use function strtolower;
use function trim;
use function ucfirst;

/**
 *
 */
class Header{

	/**
	 * Normalizes an array of header lines to format ["Name" => "Value (, Value2, Value3, ...)", ...]
	 * An exception is being made for Set-Cookie, which holds an array of values for each cookie.
	 * For multiple cookies with the same name, only the last value will be kept.
	 *
	 * @param array $headers
	 *
	 * @return array
	 */
	public static function normalize(array $headers):array{
		$normalized = [];

		foreach($headers as $key => $val){

			// the key is numeric, so $val is either a string or an array
			if(is_numeric($key)){

				// "key: val"
				if(is_string($val)){
					$header = explode(':', $val, 2);

					if(count($header) !== 2){
						continue;
					}

					$key = $header[0];
					$val = $header[1];
				}
				// [$key, $val], ["key" => $key, "val" => $val]
				elseif(is_array($val)){
					$key = array_keys($val)[0];
					$val = array_values($val)[0];
				}
				else{
					continue;
				}
			}
			// the key is named, so we assume $val holds the header values only, either as string or array
			else{
				if(is_array($val)){
					$val = implode(', ', array_values($val));
				}
			}

			$key = implode('-', array_map(fn(string $v):string => ucfirst(strtolower(trim($v))), explode('-', $key)));
			$val = trim($val);

			// skip if the header already exists but the current value is empty
			if(isset($normalized[$key]) && empty($val)){
				continue;
			}

			// cookie headers may appear multiple times
			// https://tools.ietf.org/html/rfc6265#section-4.1.2
			if($key === 'Set-Cookie'){
				// i'll just collect the last value here and leave parsing up to you :P
				$normalized[$key][strtolower(explode('=', $val, 2)[0])] = $val;
			}
			// combine header fields with the same name
			// https://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.2
			else{
				isset($normalized[$key]) && !empty($normalized[$key])
					? $normalized[$key] .= ', '.$val
					: $normalized[$key] = $val;
			}
		}

		return $normalized;
	}


}
