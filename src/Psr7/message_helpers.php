<?php
/**
 * @created      28.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use InvalidArgumentException, TypeError;
use Psr\Http\Message\{MessageInterface, RequestInterface, ResponseInterface, UploadedFileInterface, UriInterface};

use function array_combine, array_filter, array_keys, array_map, array_merge, array_values, call_user_func_array, count, explode,
	gzdecode, gzinflate, gzuncompress, implode, is_array, is_bool, is_iterable, is_numeric, is_scalar, is_string,
	json_decode, json_encode, parse_str, parse_url, rawurldecode, rawurlencode, simplexml_load_string, sort, strtolower, trim,
	ucfirst, uksort;

use const PHP_URL_QUERY, SORT_STRING;

const PSR7_INCLUDES = true;

/**
 * @link http://svn.apache.org/repos/asf/httpd/httpd/branches/1.3.x/conf/mime.types
 */
const MIMETYPES = [
	'3gp'     => 'video/3gpp',
	'7z'      => 'application/x-7z-compressed',
	'aac'     => 'audio/x-aac',
	'ai'      => 'application/postscript',
	'aif'     => 'audio/x-aiff',
	'asc'     => 'text/plain',
	'asf'     => 'video/x-ms-asf',
	'atom'    => 'application/atom+xml',
	'avi'     => 'video/x-msvideo',
	'bmp'     => 'image/bmp',
	'bz2'     => 'application/x-bzip2',
	'cer'     => 'application/pkix-cert',
	'crl'     => 'application/pkix-crl',
	'crt'     => 'application/x-x509-ca-cert',
	'css'     => 'text/css',
	'csv'     => 'text/csv',
	'cu'      => 'application/cu-seeme',
	'deb'     => 'application/x-debian-package',
	'doc'     => 'application/msword',
	'docx'    => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	'dvi'     => 'application/x-dvi',
	'eot'     => 'application/vnd.ms-fontobject',
	'eps'     => 'application/postscript',
	'epub'    => 'application/epub+zip',
	'etx'     => 'text/x-setext',
	'flac'    => 'audio/flac',
	'flv'     => 'video/x-flv',
	'gif'     => 'image/gif',
	'gz'      => 'application/gzip',
	'htm'     => 'text/html',
	'html'    => 'text/html',
	'ico'     => 'image/x-icon',
	'ics'     => 'text/calendar',
	'ini'     => 'text/plain',
	'iso'     => 'application/x-iso9660-image',
	'jar'     => 'application/java-archive',
	'jpe'     => 'image/jpeg',
	'jpeg'    => 'image/jpeg',
	'jpg'     => 'image/jpeg',
	'js'      => 'text/javascript',
	'json'    => 'application/json',
	'latex'   => 'application/x-latex',
	'log'     => 'text/plain',
	'm4a'     => 'audio/mp4',
	'm4v'     => 'video/mp4',
	'mid'     => 'audio/midi',
	'midi'    => 'audio/midi',
	'mov'     => 'video/quicktime',
	'mkv'     => 'video/x-matroska',
	'mp3'     => 'audio/mpeg',
	'mp4'     => 'video/mp4',
	'mp4a'    => 'audio/mp4',
	'mp4v'    => 'video/mp4',
	'mpe'     => 'video/mpeg',
	'mpeg'    => 'video/mpeg',
	'mpg'     => 'video/mpeg',
	'mpg4'    => 'video/mp4',
	'oga'     => 'audio/ogg',
	'ogg'     => 'audio/ogg',
	'ogv'     => 'video/ogg',
	'ogx'     => 'application/ogg',
	'pbm'     => 'image/x-portable-bitmap',
	'pdf'     => 'application/pdf',
	'pgm'     => 'image/x-portable-graymap',
	'png'     => 'image/png',
	'pnm'     => 'image/x-portable-anymap',
	'ppm'     => 'image/x-portable-pixmap',
	'ppt'     => 'application/vnd.ms-powerpoint',
	'pptx'    => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
	'ps'      => 'application/postscript',
	'qt'      => 'video/quicktime',
	'rar'     => 'application/x-rar-compressed',
	'ras'     => 'image/x-cmu-raster',
	'rss'     => 'application/rss+xml',
	'rtf'     => 'application/rtf',
	'sgm'     => 'text/sgml',
	'sgml'    => 'text/sgml',
	'svg'     => 'image/svg+xml',
	'swf'     => 'application/x-shockwave-flash',
	'tar'     => 'application/x-tar',
	'tif'     => 'image/tiff',
	'tiff'    => 'image/tiff',
	'torrent' => 'application/x-bittorrent',
	'ttf'     => 'application/x-font-ttf',
	'txt'     => 'text/plain',
	'wav'     => 'audio/x-wav',
	'webm'    => 'video/webm',
	'wma'     => 'audio/x-ms-wma',
	'wmv'     => 'video/x-ms-wmv',
	'woff'    => 'application/x-font-woff',
	'wsdl'    => 'application/wsdl+xml',
	'xbm'     => 'image/x-xbitmap',
	'xls'     => 'application/vnd.ms-excel',
	'xlsx'    => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
	'xml'     => 'application/xml',
	'xpm'     => 'image/x-xpixmap',
	'xwd'     => 'image/x-xwindowdump',
	'yaml'    => 'text/yaml',
	'yml'     => 'text/yaml',
	'zip'     => 'application/zip',
];

/**
 * Normalizes an array of header lines to format ["Name" => "Value (, Value2, Value3, ...)", ...]
 * An exception is being made for Set-Cookie, which holds an array of values for each cookie.
 * For multiple cookies with the same name, only the last value will be kept.
 *
 * @param array $headers
 *
 * @return array
 */
function normalize_message_headers(array $headers):array{
	$normalized_headers = [];

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
		if(isset($normalized_headers[$key]) && empty($val)){
			continue;
		}

		// cookie headers may appear multiple times
		// https://tools.ietf.org/html/rfc6265#section-4.1.2
		if($key === 'Set-Cookie'){
			// i'll just collect the last value here and leave parsing up to you :P
			$normalized_headers[$key][strtolower(explode('=', $val, 2)[0])] = $val;
		}
		// combine header fields with the same name
		// https://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.2
		else{
			isset($normalized_headers[$key]) && !empty($normalized_headers[$key])
				? $normalized_headers[$key] .= ', '.$val
				: $normalized_headers[$key] = $val;
		}
	}

	return $normalized_headers;
}

/**
 * @param string|string[] $data
 *
 * @return string|string[]
 * @throws \TypeError
 */
function r_rawurlencode($data){

	if(is_array($data)){
		return array_map(__FUNCTION__, $data);
	}

	if(!is_scalar($data) && $data !== null){
		throw new TypeError('$data is neither scalar nor null');
	}

	return rawurlencode((string)$data);
}

/**
 * from https://github.com/abraham/twitteroauth/blob/master/src/Util.php
 */
function build_http_query(array $params, bool $urlencode = null, string $delimiter = null, string $enclosure = null):string{

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

const BOOLEANS_AS_BOOL       = 0;
const BOOLEANS_AS_INT        = 1;
const BOOLEANS_AS_STRING     = 2;
const BOOLEANS_AS_INT_STRING = 3;

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
function clean_query_params(iterable $params, int $bool_cast = null, bool $remove_empty = null):iterable{
	$p            = [];
	$bool_cast    = $bool_cast ?? BOOLEANS_AS_BOOL;
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
		elseif(is_iterable($value)){
			$p[$key] = call_user_func_array(__FUNCTION__, [$value, $bool_cast, $remove_empty]);
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
 * @return array
 * @throws \InvalidArgumentException for unrecognized values
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
 * @param \Psr\Http\Message\MessageInterface $message
 * @param bool|null                          $assoc
 *
 * @return \stdClass|array|bool
 */
function get_json(MessageInterface $message, bool $assoc = null){
	$data = json_decode($message->getBody()->__toString(), $assoc);

	$message->getBody()->rewind();

	return $data;
}

/**
 * @param \Psr\Http\Message\MessageInterface $message
 * @param bool|null                          $assoc
 *
 * @return \SimpleXMLElement|array|bool
 */
function get_xml(MessageInterface $message, bool $assoc = null){
	$data = simplexml_load_string($message->getBody()->__toString());

	$message->getBody()->rewind();

	return $assoc === true
		? json_decode(json_encode($data), true) // cruel
		: $data;
}

/**
 * Returns the string representation of an HTTP message. (from Guzzle)
 *
 * @param \Psr\Http\Message\MessageInterface $message Message to convert to a string.
 *
 * @return string
 */
function message_to_string(MessageInterface $message):string{
	$msg = '';

	if($message instanceof RequestInterface){
		$msg = trim($message->getMethod().' '.$message->getRequestTarget()).' HTTP/'.$message->getProtocolVersion();

		if(!$message->hasHeader('host')){
			$msg .= "\r\nHost: ".$message->getUri()->getHost();
		}

	}
	elseif($message instanceof ResponseInterface){
		$msg = 'HTTP/'.$message->getProtocolVersion().' '.$message->getStatusCode().' '.$message->getReasonPhrase();
	}

	foreach($message->getHeaders() as $name => $values){
		$msg .= "\r\n".$name.': '.implode(', ', $values);
	}

	$data = $message->getBody()->__toString();
	$message->getBody()->rewind();

	return $msg."\r\n\r\n".$data;
}

/**
 * Decompresses the message content according to the Content-Encoding header and returns the decompressed data
 *
 * @param \Psr\Http\Message\MessageInterface $message
 *
 * @return string
 */
function decompress_content(MessageInterface $message):string{
	$data = $message->getBody()->__toString();
	$message->getBody()->rewind();

	switch($message->getHeaderLine('content-encoding')){
#		case 'br'      : return brotli_uncompress($data); // @todo: https://github.com/kjdev/php-ext-brotli
		case 'compress': return gzuncompress($data);
		case 'deflate' : return gzinflate($data);
		case 'gzip'    : return gzdecode($data);
		default: return $data;
	}

}

/**
 * Whether the URI is absolute, i.e. it has a scheme.
 *
 * An instance of UriInterface can either be an absolute URI or a relative reference. This method returns true
 * if it is the former. An absolute URI has a scheme. A relative reference is used to express a URI relative
 * to another URI, the base URI. Relative references can be divided into several forms:
 * - network-path references, e.g. '//example.com/path'
 * - absolute-path references, e.g. '/path'
 * - relative-path references, e.g. 'subpath'
 *
 * @see  Uri::isNetworkPathReference
 * @see  Uri::isAbsolutePathReference
 * @see  Uri::isRelativePathReference
 * @link https://tools.ietf.org/html/rfc3986#section-4
 */
function uriIsAbsolute(UriInterface $uri):bool{
	return $uri->getScheme() !== '';
}

/**
 * Whether the URI is a network-path reference.
 *
 * A relative reference that begins with two slash characters is termed an network-path reference.
 *
 * @link https://tools.ietf.org/html/rfc3986#section-4.2
 */
function uriIsNetworkPathReference(UriInterface $uri):bool{
	return $uri->getScheme() === '' && $uri->getAuthority() !== '';
}

/**
 * Whether the URI is a absolute-path reference.
 *
 * A relative reference that begins with a single slash character is termed an absolute-path reference.
 *
 * @link https://tools.ietf.org/html/rfc3986#section-4.2
 */
function uriIsAbsolutePathReference(UriInterface $uri):bool{
	return $uri->getScheme() === '' && $uri->getAuthority() === '' && isset($uri->getPath()[0]) && $uri->getPath()[0] === '/';
}

/**
 * Whether the URI is a relative-path reference.
 *
 * A relative reference that does not begin with a slash character is termed a relative-path reference.
 *
 * @return bool
 * @link https://tools.ietf.org/html/rfc3986#section-4.2
 */
function uriIsRelativePathReference(UriInterface $uri):bool{
	return $uri->getScheme() === '' && $uri->getAuthority() === '' && (!isset($uri->getPath()[0]) || $uri->getPath()[0] !== '/');
}

/**
 * removes a specific query string value.
 *
 * Any existing query string values that exactly match the provided key are
 * removed.
 *
 * @param string $key Query string key to remove.
 */
function uriWithoutQueryValue(UriInterface $uri, string $key):UriInterface{
	$current = $uri->getQuery();

	if($current === ''){
		return $uri;
	}

	$decodedKey = rawurldecode($key);

	$result = array_filter(explode('&', $current), function($part) use ($decodedKey){
		return rawurldecode(explode('=', $part)[0]) !== $decodedKey;
	});

	return $uri->withQuery(implode('&', $result));
}

/**
 * adds a specific query string value.
 *
 * Any existing query string values that exactly match the provided key are
 * removed and replaced with the given key value pair.
 *
 * A value of null will set the query string key without a value, e.g. "key"
 * instead of "key=value".
 *
 * @param string      $key   Key to set.
 * @param string|null $value Value to set
 */
function uriWithQueryValue(UriInterface $uri, string $key, string $value = null):UriInterface{
	$current = $uri->getQuery();

	if($current === ''){
		$result = [];
	}
	else{
		$decodedKey = rawurldecode($key);
		$result     = array_filter(explode('&', $current), function($part) use ($decodedKey){
			return rawurldecode(explode('=', $part)[0]) !== $decodedKey;
		});
	}

	// Query string separators ("=", "&") within the key or value need to be encoded
	// (while preventing double-encoding) before setting the query string. All other
	// chars that need percent-encoding will be encoded by withQuery().
	$replaceQuery = ['=' => '%3D', '&' => '%26'];
	$key          = strtr($key, $replaceQuery);

	$result[] = $value !== null
		? $key.'='.strtr($value, $replaceQuery)
		: $key;

	return $uri->withQuery(implode('&', $result));
}

