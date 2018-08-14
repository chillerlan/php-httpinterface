<?php
/**
 * Class HTTPFactory
 *
 * @filesource   HTTPFactory.php
 * @created      12.08.2018
 * @package      chillerlan\HTTP
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

use Psr\Http\Message\{
	RequestFactoryInterface, RequestInterface, ResponseFactoryInterface, ResponseInterface,
	ServerRequestFactoryInterface, ServerRequestInterface, StreamFactoryInterface, StreamInterface,
	UploadedFileFactoryInterface, UploadedFileInterface, UriFactoryInterface, UriInterface
};
use Fig\Http\Message\{
	RequestMethodInterface, StatusCodeInterface
};

class HTTPFactory implements RequestFactoryInterface, ServerRequestFactoryInterface,
	RequestMethodInterface, ResponseFactoryInterface, StatusCodeInterface, StreamFactoryInterface,
	UploadedFileFactoryInterface, UriFactoryInterface{

	/**
	 * @inheritdoc
	 */
	public function createRequest(string $method, $uri):RequestInterface{
		return new Request($method, $uri);
	}

	/**
	 * @inheritdoc
	 */
	public function createResponse(int $code = 200, string $reasonPhrase = ''):ResponseInterface{
		return new Response($code, null, null, null, $reasonPhrase);
	}

	/**
	 * @inheritdoc
	 */
	public function createServerRequest(string $method, $uri, array $serverParams = []):ServerRequestInterface{
		return new ServerRequest($method, $uri, null, null, null, $serverParams);
	}

	/**
	 * Return a ServerRequest populated with superglobals:
	 * $_GET
	 * $_POST
	 * $_COOKIE
	 * $_FILES
	 * $_SERVER
	 *
	 * @return ServerRequestInterface
	 */
	public function createServerRequestFromGlobals():ServerRequestInterface{

		$serverRequest = new ServerRequest(
			isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : $this::METHOD_GET,
			$this->createUriFromGlobals(),
			function_exists('getallheaders') ? getallheaders() : [],
			$this->createStream(),
			isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1',
			$_SERVER
		);

		return $serverRequest
			->withCookieParams($_COOKIE)
			->withQueryParams($_GET)
			->withParsedBody($_POST)
			->withUploadedFiles(UploadedFile::normalizeFiles($_FILES))
		;
	}

	/**
	 * @inheritdoc
	 */
	public function createStream(string $content = ''):StreamInterface{
		return Stream::create($content);
	}

	/**
	 * @inheritdoc
	 */
	public function createStreamFromFile(string $filename, string $mode = 'r'):StreamInterface{
		return new Stream(fopen($filename, $mode));
	}

	/**
	 * @inheritdoc
	 */
	public function createStreamFromResource($resource):StreamInterface{
		return new Stream($resource);
	}

	/**
	 * Create a new uploaded file.
	 *
	 * If a size is not provided it will be determined by checking the size of
	 * the file.
	 *
	 * @see http://php.net/manual/features.file-upload.post-method.php
	 * @see http://php.net/manual/features.file-upload.errors.php
	 *
	 * @param StreamInterface $stream          Underlying stream representing the
	 *                                         uploaded file content.
	 * @param int             $size            in bytes
	 * @param int             $error           PHP file upload error
	 * @param string          $clientFilename  Filename as provided by the client, if any.
	 * @param string          $clientMediaType Media type as provided by the client, if any.
	 *
	 * @return UploadedFileInterface
	 *
	 * @throws \InvalidArgumentException If the file resource is not readable.
	 */
	public function createUploadedFile(StreamInterface $stream, int $size = null, int $error = \UPLOAD_ERR_OK, string $clientFilename = null, string $clientMediaType = null):UploadedFileInterface{
		return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
	}

	/**
	 * @inheritdoc
	 */
	public function createUri(string $uri = ''):UriInterface{
		return new Uri($uri);
	}

	/**
	 * @see \parse_url()
	 *
	 * @param array $parts
	 *
	 * @return \Psr\Http\Message\UriInterface
	 */
	public function createUriFromParts(array $parts):UriInterface{
		return Uri::fromParts($parts);
	}

	/**
	 * Get a Uri populated with values from $_SERVER.
	 *
	 * @return \Psr\Http\Message\UriInterface
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

}
