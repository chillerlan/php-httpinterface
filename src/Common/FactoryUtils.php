<?php
/**
 * Class FactoryUtils
 *
 * @created      20.10.2022
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2022 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Common;

use chillerlan\HTTP\Psr7\Stream;
use chillerlan\HTTP\Utils\StreamUtil;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException, Stringable;
use function fseek, gettype, is_scalar, stream_copy_to_stream, stream_get_meta_data;

/**
 *
 */
class FactoryUtils{

	/**
	 * Create a new writable stream from a string.
	 */
	public static function createStream(string $content = '', string $mode = 'r+', bool $rewind = true):StreamInterface{

		if(!StreamUtil::modeAllowsWrite($mode)){
			throw new InvalidArgumentException('invalid mode for writing');
		}

		$stream = new Stream(StreamUtil::tryFopen('php://temp', $mode));

		if($content !== ''){
			$stream->write($content);
		}

		if($rewind === true){
			$stream->rewind();
		}

		return $stream;
	}

	/**
	 *
	 */
	public static function createStreamFromSource(mixed $source = null):StreamInterface{
		$source ??= '';

		if($source instanceof StreamInterface){
			return $source;
		}

		if($source instanceof Stringable){
			return self::createStream((string)$source);
		}

		if(is_scalar($source)){
			return self::createStream((string)$source);
		}

		$type = gettype($source);

		if($type === 'resource'){
			// avoid using php://input and copy over the contents to a new stream
			if((stream_get_meta_data($source)['uri'] ?? '') === 'php://input'){
				$stream = StreamUtil::tryFopen('php://temp', 'r+');

				stream_copy_to_stream($source, $stream);
				fseek($stream, 0);

				return new Stream($stream);
			}

			return new Stream($source);
		}

#		if($type === 'object'){}

		if($type === 'NULL'){
			return self::createStream();
		}

		throw new InvalidArgumentException('Invalid resource type: '.$type);
	}

}
