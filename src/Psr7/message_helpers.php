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
use Psr\Http\Message\{MessageInterface, RequestInterface, ResponseInterface, UploadedFileInterface};

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

			if(is_string($val)){
				$header = explode(':', $val, 2);

				if(count($header) !== 2){
					continue;
				}

				$key = $header[0];
				$val = $header[1];
			}
			elseif(is_array($val)){
				$key = array_keys($val)[0];
				$val = array_values($val)[0];
			}
			else{
				continue;
			}
		}

		$key = strtolower(trim($key));

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

	return rawurlencode($data);
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

const BOOLEANS_AS_BOOL = 0;
const BOOLEANS_AS_INT = 1;
const BOOLEANS_AS_STRING = 2;
const BOOLEANS_AS_INT_STRING = 3;
/**
 * @param iterable  $params
 * @param int|null  $bool_cast converts booleans to a type determined like following:
 *                             BOOLEANS_AS_BOOL      : unchanged boolean value (default)
 *                             BOOLEANS_AS_INT       : integer values 0 or 1
 *                             BOOLEANS_AS_STRING    : "true"/"false" strings
 *                             BOOLEANS_AS_INT_STRING: "0"/"1"
 *
 * @param bool|null $remove_empty remove empty and NULL values
 *
 * @return array
 */
function clean_query_params(iterable $params, int $bool_cast = null, bool $remove_empty = null):array{
	$p = [];
	$bool_cast = $bool_cast ?? BOOLEANS_AS_BOOL;
	$remove_empty = $remove_empty ?? true;

	foreach($params as $key => $value){

		if(is_bool($value)){

			if($bool_cast === BOOLEANS_AS_BOOL){
				$p[$key] = $value;
			}
			elseif($bool_cast === BOOLEANS_AS_INT){
				$p[$key] = (int)$value;
			}
			elseif($bool_cast === BOOLEANS_AS_STRING){
				$p[$key] = $value ? 'true' : 'false';
			}
			elseif($bool_cast === BOOLEANS_AS_INT_STRING){
				$p[$key] = (string)(int)$value;
			}

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

/**
 * @todo
 *
 * @param \Psr\Http\Message\ResponseInterface $response
 * @param bool|null                           $assoc
 *
 * @return mixed
 */
function get_json(ResponseInterface $response, bool $assoc = null){
	return json_decode($response->getBody()->getContents(), $assoc);
}

/**
 * @todo
 *
 * @param \Psr\Http\Message\ResponseInterface $response
 *
 * @return \SimpleXMLElement
 */
function get_xml(ResponseInterface $response){
	return simplexml_load_string($response->getBody()->getContents());
}

/**
 * Returns the string representation of an HTTP message. (from Guzzle)
 *
 * @param MessageInterface $message Message to convert to a string.
 *
 * @return string
 */
function message_to_string(MessageInterface $message){

	if($message instanceof RequestInterface){
		$msg = trim($message->getMethod().' '.$message->getRequestTarget()).' HTTP/'.$message->getProtocolVersion();

		if(!$message->hasHeader('host')){
			$msg .= "\r\nHost: ".$message->getUri()->getHost();
		}

	}
	elseif($message instanceof ResponseInterface){
		$msg = 'HTTP/'.$message->getProtocolVersion().' '.$message->getStatusCode().' '.$message->getReasonPhrase();
	}
	else{
		throw new \InvalidArgumentException('Unknown message type');
	}

	foreach($message->getHeaders() as $name => $values){
		$msg .= "\r\n{$name}: ".implode(', ', $values);
	}

	return "{$msg}\r\n\r\n".$message->getBody();
}
