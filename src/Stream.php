<?php
/**
 * Class Stream
 *
 * @filesource   Stream.php
 * @created      11.08.2018
 * @package      chillerlan\HTTP
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

use Exception;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class Stream implements StreamInterface{

	private const MODES_READ = [
		'a+'  => true,
		'c+'  => true,
		'c+b' => true,
		'c+t' => true,
		'r'   => true,
		'r+'  => true,
		'rb'  => true,
		'rt'  => true,
		'r+b' => true,
		'r+t' => true,
		'w+'  => true,
		'w+b' => true,
		'w+t' => true,
		'x+'  => true,
		'x+b' => true,
		'x+t' => true,
	];

	private const MODES_WRITE = [
		'a'   => true,
		'a+'  => true,
		'c+'  => true,
		'c+b' => true,
		'c+t' => true,
		'r+'  => true,
		'rw'  => true,
		'r+b' => true,
		'r+t' => true,
		'w'   => true,
		'w+'  => true,
		'wb'  => true,
		'w+b' => true,
		'w+t' => true,
		'x+'  => true,
		'x+b' => true,
		'x+t' => true,
	];

	/**
	 * @var resource
	 */
	private $stream;

	/**
	 * @var bool
	 */
	private $seekable;

	/**
	 * @var bool
	 */
	private $readable;

	/**
	 * @var bool
	 */
	private $writable;

	/**
	 * @var string|null
	 */
	private $uri;

	/**
	 * @var int|null
	 */
	private $size;

	/**
	 * Stream constructor.
	 *
	 * @param resource $stream
	 */
	public function __construct($stream){

		if(!is_resource($stream)){
			throw new InvalidArgumentException('Stream must be a resource');
		}

		$this->stream = $stream;

		$meta = stream_get_meta_data($this->stream);

		$this->seekable = $meta['seekable'];
		$this->readable = isset($this::MODES_READ[$meta['mode']]);
		$this->writable = isset($this::MODES_WRITE[$meta['mode']]);
		$this->uri      = $meta['uri'] ?? null;
	}

	/**
	 * Closes the stream when the destructed
	 *
	 * @return void
	 */
	public function __destruct(){
		$this->close();
	}

	/**
	 * @inheritdoc
	 */
	public function __toString(){

		if(!is_resource($this->stream)){
			return '';
		}

		try{
			$this->seek(0);

			return (string)stream_get_contents($this->stream);
		}
		catch(Exception $e){
			return '';
		}

	}

	/**
	 * @inheritdoc
	 */
	public function close():void{

		if(is_resource($this->stream)){
			fclose($this->stream);
		}

		$this->detach();
	}

	/**
	 * @inheritdoc
	 */
	public function detach(){
		$oldResource = $this->stream;

		$this->stream   = null;
		$this->size     = null;
		$this->uri      = null;
		$this->readable = false;
		$this->writable = false;
		$this->seekable = false;

		return $oldResource;
	}

	/**
	 * @inheritdoc
	 */
	public function getSize():?int{

		if($this->size !== null){
			return $this->size;
		}

		if(!isset($this->stream)){
			return null;
		}

		// Clear the stat cache if the stream has a URI
		if($this->uri){
			clearstatcache(true, $this->uri);
		}

		$stats = fstat($this->stream);

		if(isset($stats['size'])){
			$this->size = $stats['size'];

			return $this->size;
		}

		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function tell():int{
		$result = ftell($this->stream);

		if($result === false){
			throw new RuntimeException('Unable to determine stream position');
		}

		return $result;
	}

	/**
	 * @inheritdoc
	 */
	public function eof():bool{
		return !$this->stream || feof($this->stream);
	}

	/**
	 * @inheritdoc
	 */
	public function isSeekable():bool{
		return $this->seekable;
	}

	/**
	 * @inheritdoc
	 */
	public function seek($offset, $whence = SEEK_SET):void{

		if(!$this->seekable){
			throw new RuntimeException('Stream is not seekable');
		}
		elseif(fseek($this->stream, $offset, $whence) === -1){
			throw new RuntimeException('Unable to seek to stream position '.$offset.' with whence '.var_export($whence, true));
		}

	}

	/**
	 * @inheritdoc
	 */
	public function rewind():void{
		$this->seek(0);
	}

	/**
	 * @inheritdoc
	 */
	public function isWritable():bool{
		return $this->writable;
	}

	/**
	 * @inheritdoc
	 */
	public function write($string):int{

		if(!$this->writable){
			throw new RuntimeException('Cannot write to a non-writable stream');
		}

		// We can't know the size after writing anything
		$this->size = null;
		$result     = fwrite($this->stream, $string);

		if($result === false){
			throw new RuntimeException('Unable to write to stream');
		}

		return $result;
	}

	/**
	 * @inheritdoc
	 */
	public function isReadable():bool{
		return $this->readable;
	}

	/**
	 * @inheritdoc
	 */
	public function read($length):string{

		if(!$this->readable){
			throw new RuntimeException('Cannot read from non-readable stream');
		}
		if($length < 0){
			throw new RuntimeException('Length parameter cannot be negative');
		}

		if($length === 0){
			return '';
		}

		$string = fread($this->stream, $length);

		if($string === false){
			throw new RuntimeException('Unable to read from stream');
		}

		return $string;
	}

	/**
	 * @inheritdoc
	 */
	public function getContents():string{
		$contents = stream_get_contents($this->stream);

		if($contents === false){
			throw new RuntimeException('Unable to read stream contents');
		}

		return $contents;
	}

	/**
	 * Get stream metadata as an associative array or retrieve a specific key.
	 *
	 * The keys returned are identical to the keys returned from PHP's
	 * stream_get_meta_data() function.
	 *
	 * @link http://php.net/manual/en/function.stream-get-meta-data.php
	 *
	 * @param string $key Specific metadata to retrieve.
	 *
	 * @return array|mixed|null Returns an associative array if no key is
	 *     provided. Returns a specific key value if a key is provided and the
	 *     value is found, or null if the key is not found.
	 */
	public function getMetadata($key = null){

		if(!isset($this->stream)){
			return $key ? null : [];
		}
		elseif($key === null){
			return stream_get_meta_data($this->stream);
		}

		$meta = stream_get_meta_data($this->stream);

		return isset($meta[$key]) ? $meta[$key] : null;
	}

	/**
	 * @param string|null $content
	 *
	 * @return \Psr\Http\Message\StreamInterface
	 */
	public static function create(string $content = ''):StreamInterface{
		$stream = fopen('php://temp', 'r+');

		if($content !== ''){
			fwrite($stream, $content);
			fseek($stream, 0);
		}

		return new self($stream);
	}

	/**
	 * @param mixed $in
	 *
	 * @return \Psr\Http\Message\StreamInterface
	 */
	public static function fromInputGuess($in = null):StreamInterface{
		$in = $in ?? '';

		if(is_string($in) && is_file($in) && is_readable($in)){
			return new self(fopen($in, 'r'));
		}

		if(is_scalar($in)){
			return self::create((string)$in);
		}

		$type = gettype($in);

		if($type === 'resource'){
			return new self($in);
		}
		elseif($type === 'object'){

			if($in instanceof StreamInterface){
				return $in;
			}
			elseif(method_exists($in, '__toString')){
				return self::create((string)$in);
			}

		}

		throw new InvalidArgumentException('Invalid resource type: '.$type);
	}

}
