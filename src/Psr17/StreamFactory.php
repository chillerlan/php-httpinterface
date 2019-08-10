<?php
/**
 * Class StreamFactory
 *
 * @filesource   StreamFactory.php
 * @created      27.08.2018
 * @package      chillerlan\HTTP\Psr17
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr17;

use chillerlan\HTTP\Psr7\Stream;
use Psr\Http\Message\{StreamFactoryInterface, StreamInterface};
use InvalidArgumentException, RuntimeException;

use function fopen, is_file;

final class StreamFactory implements StreamFactoryInterface{

	/**
	 * Create a new stream from a string.
	 *
	 * The stream SHOULD be created with a temporary resource.
	 *
	 * @param string $content String content with which to populate the stream.
	 *
	 * @return \Psr\Http\Message\StreamInterface
	 */
	public function createStream(string $content = ''):StreamInterface{
		return create_stream($content);
	}

	/**
	 * Create a stream from an existing file.
	 *
	 * The file MUST be opened using the given mode, which may be any mode
	 * supported by the `fopen` function.
	 *
	 * The `$filename` MAY be any string supported by `fopen()`.
	 *
	 * @param string $filename Filename or stream URI to use as basis of stream.
	 * @param string $mode     Mode with which to open the underlying filename/stream.
	 *
	 * @return \Psr\Http\Message\StreamInterface
	 */
	public function createStreamFromFile(string $filename, string $mode = 'r'):StreamInterface{

		if(empty($filename) || !is_file($filename)){
			throw new RuntimeException('invalid file');
		}

		if(!isset(Stream::MODES_WRITE[$mode]) && !isset(Stream::MODES_READ[$mode])){
			throw new InvalidArgumentException('invalid mode');
		}

		return new Stream(fopen($filename, $mode));
	}

	/**
	 * Create a new stream from an existing resource.
	 *
	 * The stream MUST be readable and may be writable.
	 *
	 * @param resource $resource PHP resource to use as basis of stream.
	 *
	 * @return \Psr\Http\Message\StreamInterface
	 */
	public function createStreamFromResource($resource):StreamInterface{
		return new Stream($resource);
	}

}
