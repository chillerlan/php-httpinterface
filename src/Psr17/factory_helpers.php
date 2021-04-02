<?php
/**
 * @created      28.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr17;

use Psr\Http\Message\StreamInterface;
use chillerlan\HTTP\Psr7\Stream;
use InvalidArgumentException;

use function in_array, is_scalar, method_exists;

const CHILLERLAN_PSR17_INCLUDES = true;

const STREAM_MODES_READ_WRITE = ['a+', 'c+', 'c+b', 'c+t', 'r+' , 'r+b', 'r+t', 'w+' , 'w+b', 'w+t', 'x+' , 'x+b', 'x+t'];
const STREAM_MODES_READ = [...STREAM_MODES_READ_WRITE, 'r', 'rb', 'rt'];
const STREAM_MODES_WRITE = [...STREAM_MODES_READ_WRITE, 'a', 'rw', 'w', 'wb'];

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

	if(!in_array($mode, STREAM_MODES_WRITE)){
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
	$in ??= '';

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
