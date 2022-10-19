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

use function fopen, in_array, is_file, is_readable;

class StreamFactory implements StreamFactoryInterface{

	/**
	 * @inheritDoc
	 */
	public function createStream(string $content = ''):StreamInterface{
		$stream = new Stream(fopen('php://temp', 'r+'));

		if($content !== ''){
			$stream->write($content);
		}

		return $stream;
	}

	/**
	 * @inheritDoc
	 */
	public function createStreamFromFile(string $filename, string $mode = 'r'):StreamInterface{

		if(empty($filename) || !is_file($filename) || !is_readable($filename)){
			throw new RuntimeException('invalid file');
		}

		if(!in_array($mode, FactoryHelpers::STREAM_MODES_WRITE) && !in_array($mode, FactoryHelpers::STREAM_MODES_READ)){
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
