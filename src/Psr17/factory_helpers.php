<?php
/**
 * @created      28.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr17;

use Psr\Http\Message\{
	ServerRequestFactoryInterface, ServerRequestInterface, StreamInterface, UriInterface, UriFactoryInterface
};
use chillerlan\HTTP\Psr7\{File, Stream};
use InvalidArgumentException;

use function explode, function_exists, getallheaders, is_scalar, method_exists, substr;

const PSR17_INCLUDES = true;

const STREAM_MODES_READ_WRITE = [
	'a+'  => true,
	'c+'  => true,
	'c+b' => true,
	'c+t' => true,
	'r+'  => true,
	'r+b' => true,
	'r+t' => true,
	'w+'  => true,
	'w+b' => true,
	'w+t' => true,
	'x+'  => true,
	'x+b' => true,
	'x+t' => true,
];

const STREAM_MODES_READ = STREAM_MODES_READ_WRITE + [
	'r'   => true,
	'rb'  => true,
	'rt'  => true,
];

const STREAM_MODES_WRITE = STREAM_MODES_READ_WRITE + [
	'a'   => true,
	'rw'  => true,
	'w'   => true,
	'wb'  => true,
];

/**
 * Return a ServerRequest populated with superglobals:
 * $_GET
 * $_POST
 * $_COOKIE
 * $_FILES
 * $_SERVER
 */
function create_server_request_from_globals(
	ServerRequestFactoryInterface $serverRequestFactory,
	UriFactoryInterface $uriFactory
):ServerRequestInterface{

	$serverRequest = $serverRequestFactory->createServerRequest(
		$_SERVER['REQUEST_METHOD'] ?? 'GET',
		create_uri_from_globals($uriFactory),
		$_SERVER
	);

	if(function_exists('getallheaders')){
		foreach(getallheaders() ?: [] as $name => $value){
			$serverRequest = $serverRequest->withHeader($name, $value);
		}
	}

	return $serverRequest
		->withProtocolVersion(isset($_SERVER['SERVER_PROTOCOL']) ? substr($_SERVER['SERVER_PROTOCOL'], 5) : '1.1')
		->withCookieParams($_COOKIE)
		->withQueryParams($_GET)
		->withParsedBody($_POST)
		->withUploadedFiles(File::normalize($_FILES))
	;
}

/**
 * Create a Uri populated with values from $_SERVER.
 */
function create_uri_from_globals(UriFactoryInterface $uriFactory):UriInterface{
	$hasPort  = false;
	$hasQuery = false;

	$uri = $uriFactory->createUri()
		->withScheme(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');

	if(isset($_SERVER['HTTP_HOST'])){
		$hostHeaderParts = explode(':', $_SERVER['HTTP_HOST']);
		$uri = $uri->withHost($hostHeaderParts[0]);

		if(isset($hostHeaderParts[1])){
			$hasPort       = true;
			$uri = $uri->withPort($hostHeaderParts[1]);
		}
	}
	elseif(isset($_SERVER['SERVER_NAME'])){
		$uri = $uri->withHost($_SERVER['SERVER_NAME']);
	}
	elseif(isset($_SERVER['SERVER_ADDR'])){
		$uri = $uri->withHost($_SERVER['SERVER_ADDR']);
	}

	if(!$hasPort && isset($_SERVER['SERVER_PORT'])){
		$uri = $uri->withPort($_SERVER['SERVER_PORT']);
	}

	if(isset($_SERVER['REQUEST_URI'])){
		$requestUriParts = explode('?', $_SERVER['REQUEST_URI']);
		$uri = $uri->withPath($requestUriParts[0]);

		if(isset($requestUriParts[1])){
			$hasQuery       = true;
			$uri = $uri->withQuery($requestUriParts[1]);
		}
	}

	if(!$hasQuery && isset($_SERVER['QUERY_STRING'])){
		$uri = $uri->withQuery($_SERVER['QUERY_STRING']);
	}

	return $uri;
}

/**
 * Create a new writable stream from a string.
 *
 * The stream SHOULD be created with a temporary resource.
 *
 * @param string $content String content with which to populate the stream.
 * @param string $mode    one of \chillerlan\HTTP\Psr17\STREAM_MODES_WRITE
 * @param bool   $rewind  rewind the stream
 *
 * @return \Psr\Http\Message\StreamInterface
 */
function create_stream(string $content = '', string $mode = 'r+', bool $rewind = true):StreamInterface{

	if(!isset(STREAM_MODES_WRITE[$mode])){
		throw new InvalidArgumentException('invalid mode');
	}

	$stream = new Stream(fopen('php://temp', $mode));

	if($content !== ''){
		$stream->write($content);
	}

	if($rewind){
		$stream->rewind();
	}

	return $stream;
}

/**
 * @param mixed $in
 *
 * @return \Psr\Http\Message\StreamInterface
 */
function create_stream_from_input($in = null):StreamInterface{
	$in = $in ?? '';

	// not sure about this one, it might cause:
	// a) trouble if the given string accidentally matches a file path, and
	// b) security implications because of the above.
	// use with caution and never with user input!
#	if(\is_string($in) && \is_file($in) && \is_readable($in)){
#		return new Stream(\fopen($in, 'r'));
#	}

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
