<?php
/**
 * Class StreamFactory
 *
 * @created      27.08.2018
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
	 * @inheritDoc
	 */
	public function createStream(string $content = ''):StreamInterface{
		return create_stream($content);
	}

	/**
	 * @inheritDoc
	 */
	public function createStreamFromFile(string $filename, string $mode = 'r'):StreamInterface{

		if(empty($filename) || !is_file($filename)){
			throw new RuntimeException('invalid file');
		}

		if(!isset(STREAM_MODES_WRITE[$mode]) && !isset(STREAM_MODES_READ[$mode])){
			throw new InvalidArgumentException('invalid mode');
		}

		return new Stream(fopen($filename, $mode));
	}

	/**
	 * @inheritDoc
	 */
	public function createStreamFromResource($resource):StreamInterface{
		return new Stream($resource);
	}

}
