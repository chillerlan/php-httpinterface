<?php
/**
 * @filesource   factory_helpers.php
 * @created      28.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr17;

use chillerlan\HTTP\Psr7;
use chillerlan\HTTP\Psr7\{ServerRequest, Stream, Uri};
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

const PSR17_INCLUDES = true;

/**
 * Return a ServerRequest populated with superglobals:
 * $_GET
 * $_POST
 * $_COOKIE
 * $_FILES
 * $_SERVER
 *
 * @return \chillerlan\HTTP\Psr7\ServerRequest|\Psr\Http\Message\ServerRequestInterface
 */
function create_server_request_from_globals():ServerRequest{

	$serverRequest = new ServerRequest(
		isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : ServerRequest::METHOD_GET,
		create_uri_from_globals(),
		function_exists('getallheaders') ? getallheaders() : [],
		(new StreamFactory)->createStream(),
		isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1',
		$_SERVER
	);

	return $serverRequest
		->withCookieParams($_COOKIE)
		->withQueryParams($_GET)
		->withParsedBody($_POST)
		->withUploadedFiles(Psr7\normalize_files($_FILES))
	;
}

/**
 * Get a Uri populated with values from $_SERVER.
 *
 * @return \chillerlan\HTTP\Psr7\Uri|\Psr\Http\Message\UriInterface
 */
function create_uri_from_globals():Uri{
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
 * Create a new stream from a string.
 *
 * The stream SHOULD be created with a temporary resource.
 *
 * @param string $content String content with which to populate the stream.
 *
 * @return \chillerlan\HTTP\Psr7\Stream|\Psr\Http\Message\StreamInterface
 */
function create_stream(string $content = ''):Stream{
	$stream = fopen('php://temp', 'r+');

	if($content !== ''){
		fwrite($stream, $content);
		fseek($stream, 0);
	}

	return new Stream($stream);
}

/**
 * @param mixed $in
 *
 * @return \chillerlan\HTTP\Psr7\Stream|\Psr\Http\Message\StreamInterface
 */
function create_stream_from_input($in = null):StreamInterface{
	$in = $in ?? '';

	// not sure about this one, it might cause:
	// a) trouble if the given string accidentally matches a file path, and
	// b) security implications because of the above.
	// use with caution and never with user input!
	if(is_string($in) && is_file($in) && is_readable($in)){
		return new Stream(fopen($in, 'r'));
	}

	if(is_scalar($in)){
		return create_stream((string)$in);
	}

	$type = gettype($in);

	if($type === 'resource'){
		return new Stream($in);
	}
	elseif($type === 'object'){

		if($in instanceof StreamInterface){
			return $in;
		}
		elseif(method_exists($in, '__toString')){
			return create_stream((string)$in);
		}

	}

	throw new InvalidArgumentException('Invalid resource type: '.$type);
}
