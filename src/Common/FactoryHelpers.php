<?php
/**
 * Class FactoryHelpers
 *
 * @created      20.10.2022
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2022 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Common;

use chillerlan\HTTP\Psr7\Stream;
use chillerlan\HTTP\Utils\StreamUtil;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Stringable;
use function fseek;
use function gettype;
use function is_scalar;
use function stream_copy_to_stream;
use function stream_get_meta_data;

/**
 *
 */
class FactoryHelpers{

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

		if($rewind){
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
