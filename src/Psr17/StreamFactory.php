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

use chillerlan\HTTP\Common\FactoryUtils;
use chillerlan\HTTP\Psr7\Stream;
use chillerlan\HTTP\Utils\StreamUtil;
use Psr\Http\Message\{StreamFactoryInterface, StreamInterface};
use RuntimeException;
use function is_file, is_readable;

/**
 *
 */
class StreamFactory implements StreamFactoryInterface{

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

}
