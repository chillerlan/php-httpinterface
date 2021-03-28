<?php
/**
 * @created      28.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use TypeError;
use Psr\Http\Message\{MessageInterface, RequestInterface, ResponseInterface, UriInterface};

use function array_filter, array_map, explode, gzdecode, gzinflate, gzuncompress, implode,
	is_array, is_scalar, json_decode, json_encode, parse_url, preg_match, preg_replace_callback, rawurldecode,
	rawurlencode, simplexml_load_string, trim, urlencode;

const PSR7_INCLUDES = true;

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

const URI_DEFAULT_PORTS = [
	'http'   => 80,
	'https'  => 443,
	'ftp'    => 21,
	'gopher' => 70,
	'nntp'   => 119,
	'news'   => 119,
	'telnet' => 23,
	'tn3270' => 23,
	'imap'   => 143,
	'pop'    => 110,
	'ldap'   => 389,
];

function uriIsDefaultPort(UriInterface $uri):bool{
	$port   = $uri->getPort();
	$scheme = $uri->getScheme();

	return $port === null || (isset(URI_DEFAULT_PORTS[$scheme]) && $port === URI_DEFAULT_PORTS[$scheme]);
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

/**
 * UTF-8 aware \parse_url() replacement.
 *
 * The internal function produces broken output for non ASCII domain names
 * (IDN) when used with locales other than "C".
 *
 * On the other hand, cURL understands IDN correctly only when UTF-8 locale
 * is configured ("C.UTF-8", "en_US.UTF-8", etc.).
 *
 * @see https://bugs.php.net/bug.php?id=52923
 * @see https://www.php.net/manual/en/function.parse-url.php#114817
 * @see https://curl.haxx.se/libcurl/c/CURLOPT_URL.html#ENCODING
 *
 * @link https://github.com/guzzle/psr7/blob/c0dcda9f54d145bd4d062a6d15f54931a67732f9/src/Uri.php#L89-L130
 */
function parseUrl(string $url):?array{
	// If IPv6
	$prefix = '';
	/** @noinspection RegExpRedundantEscape */
	if(preg_match('%^(.*://\[[0-9:a-f]+\])(.*?)$%', $url, $matches)){
		/** @var array{0:string, 1:string, 2:string} $matches */
		$prefix = $matches[1];
		$url    = $matches[2];
	}

	$encodedUrl = preg_replace_callback('%[^:/@?&=#]+%usD', fn($matches) => urlencode($matches[0]), $url);
	$result     = parse_url($prefix.$encodedUrl);

	if($result === false){
		return null;
	}

	return array_map('urldecode', $result);
}
