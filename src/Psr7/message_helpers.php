<?php
/**
 * @filesource   message_helpers.php
 * @created      28.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;

const PSR7_INCLUDES = true;

/**
 * Normalizes an array of header lines to format "Name: Value"
 *
 * @param array $headers
 *
 * @return array
 */
function normalize_request_headers(array $headers):array{
	$normalized_headers = [];

	foreach($headers as $key => $val){

		if(is_numeric($key)){
			$header = explode(':', $val, 2);

			if(count($header) !== 2){
				continue;
			}

			$key = $header[0];
			$val = $header[1];
		}

		$key = ucfirst(strtolower(trim($key)));

		$normalized_headers[$key] = trim($val);
	}

	return $normalized_headers;
}

/**
 * @param mixed $data
 *
 * @return mixed
 */
function raw_urlencode($data){

	if(is_array($data)){
		return array_map(__NAMESPACE__.'\\raw_urlencode', $data);
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
function build_http_query(array $params, bool $urlencode = null, string $delimiter = null, string $enclosure = null):string{

	if(empty($params)){
		return '';
	}

	// urlencode both keys and values
	if($urlencode ?? true){
		$params = array_combine(
			raw_urlencode(array_keys($params)),
			raw_urlencode(array_values($params))
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
 * @param array     $params
 * @param bool|null $booleans_as_string - converts booleans to "true"/"false" strings if set to true, "0"/"1" otherwise.
 *
 * @return array
 */
function clean_query_params(array $params, bool $booleans_as_string = null):array{

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

/**
 * merges additional query parameters into an existing query string
 *
 * @param string $uri
 * @param array  $query
 *
 * @return string
 */
function merge_query(string $uri, array $query):string{
	parse_str(parse_url($uri, PHP_URL_QUERY), $parsedquery);

	$requestURI = explode('?', $uri)[0];
	$params     = array_merge($parsedquery, $query);

	if(!empty($params)){
		$requestURI .= '?'.build_http_query($params);
	}

	return $requestURI;
}

/**
 * Return an UploadedFile instance array.
 *
 * @param array $files A array which respect $_FILES structure
 *
 * @throws \InvalidArgumentException for unrecognized values
 * @return array
 */
function normalize_files(array $files):array{
	$normalized = [];

	foreach($files as $key => $value){

		if($value instanceof UploadedFileInterface){
			$normalized[$key] = $value;
		}
		elseif(is_array($value) && isset($value['tmp_name'])){
			$normalized[$key] = create_uploaded_file_from_spec($value);
		}
		elseif(is_array($value)){
			$normalized[$key] = normalize_files($value);
			continue;
		}
		else{
			throw new InvalidArgumentException('Invalid value in files specification');
		}

	}

	return $normalized;
}

/**
 * Create and return an UploadedFile instance from a $_FILES specification.
 *
 * If the specification represents an array of values, this method will
 * delegate to normalizeNestedFileSpec() and return that return value.
 *
 * @param array $value $_FILES struct
 *
 * @return array|\Psr\Http\Message\UploadedFileInterface
 */
function create_uploaded_file_from_spec(array $value){

	if(is_array($value['tmp_name'])){
		return normalize_nested_file_spec($value);
	}

	return new UploadedFile($value['tmp_name'], (int)$value['size'], (int)$value['error'], $value['name'], $value['type']);
}

/**
 * Normalize an array of file specifications.
 *
 * Loops through all nested files and returns a normalized array of
 * UploadedFileInterface instances.
 *
 * @param array $files
 *
 * @return \Psr\Http\Message\UploadedFileInterface[]
 */
function normalize_nested_file_spec(array $files = []):array{
	$normalizedFiles = [];

	foreach(array_keys($files['tmp_name']) as $key){
		$spec = [
			'tmp_name' => $files['tmp_name'][$key],
			'size'     => $files['size'][$key],
			'error'    => $files['error'][$key],
			'name'     => $files['name'][$key],
			'type'     => $files['type'][$key],
		];

		$normalizedFiles[$key] = create_uploaded_file_from_spec($spec);
	}

	return $normalizedFiles;
}
