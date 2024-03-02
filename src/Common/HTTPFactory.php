<?php
/**
 * Class HTTPFactory
 *
 * @created      02.03.2024
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2024 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Common;

use chillerlan\HTTP\Psr7\{Request, Response, ServerRequest, Stream, UploadedFile, Uri};
use chillerlan\HTTP\Utils\StreamUtil;
use Fig\Http\Message\{RequestMethodInterface, StatusCodeInterface};
use Psr\Http\Message\{
	RequestFactoryInterface, RequestInterface, ResponseFactoryInterface, ResponseInterface, ServerRequestFactoryInterface,
	ServerRequestInterface, StreamFactoryInterface, StreamInterface, UploadedFileFactoryInterface, UploadedFileInterface,
	UriFactoryInterface, UriInterface
};
use RuntimeException;
use function is_file,is_readable;
use const UPLOAD_ERR_OK;

/**
 * Implements the PSR-17 HTTP factories
 */
class HTTPFactory implements
	RequestFactoryInterface,
	ResponseFactoryInterface,
	RequestMethodInterface,
	ServerRequestFactoryInterface,
	StatusCodeInterface,
	StreamFactoryInterface,
	UploadedFileFactoryInterface,
	UriFactoryInterface {

	/**
	 * @inheritDoc
	 */
	public function createRequest(string $method, $uri):RequestInterface{
		return new Request($method, $uri);
	}

	/**
	 * @inheritDoc
	 */
	public function createResponse(int $code = 200, string $reasonPhrase = ''):ResponseInterface{
		return new Response($code, $reasonPhrase);
	}

	/**
	 * @inheritDoc
	 */
	public function createStream(string $content = ''):StreamInterface{
		return FactoryUtils::createStream(content: $content, rewind: false);
	}

	/**
	 * @inheritDoc
	 */
	public function createStreamFromFile(string $filename, string $mode = 'r'):StreamInterface{

		if(empty($filename) || !is_file($filename) || !is_readable($filename)){
			throw new RuntimeException('invalid file');
		}

		return new Stream(StreamUtil::tryFopen($filename, $mode));
	}

	/**
	 * @inheritDoc
	 */
	public function createStreamFromResource($resource):StreamInterface{
		return new Stream($resource);
	}

	/**
	 * @inheritDoc
	 */
	public function createUri(string $uri = ''):UriInterface{
		return new Uri($uri);
	}

	/**
	 * @inheritDoc
	 */
	public function createServerRequest(string $method, $uri, array $serverParams = []):ServerRequestInterface{
		return new ServerRequest($method, $uri, $serverParams);
	}

	/**
	 * @inheritDoc
	 */
	public function createUploadedFile(
		StreamInterface $stream,
		int|null        $size = null,
		int             $error = UPLOAD_ERR_OK,
		string|null     $clientFilename = null,
		string|null     $clientMediaType = null,
	):UploadedFileInterface{
		return new UploadedFile($stream, ($size ?? (int)$stream->getSize()), $error, $clientFilename, $clientMediaType);
	}

}
