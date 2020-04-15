<?php
/**
 * Class Stream
 *
 * @filesource   Stream.php
 * @created      11.08.2018
 * @package      chillerlan\HTTP\Psr7
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use Exception, InvalidArgumentException, RuntimeException;

use function clearstatcache, fclose, feof, fread, fstat, ftell, fwrite, is_resource, stream_get_contents,
	stream_get_meta_data, trigger_error;

use const E_USER_ERROR, SEEK_SET;
use const chillerlan\HTTP\Psr17\{STREAM_MODES_READ, STREAM_MODES_WRITE};

/**
 * @property resource $stream
 */
final class Stream extends StreamAbstract{

	/**
	 * @var bool
	 */
	private bool $seekable;

	/**
	 * @var bool
	 */
	private bool $readable;

	/**
	 * @var bool
	 */
	private bool $writable;

	/**
	 * @var string|null
	 */
	private ?string $uri = null;

	/**
	 * @var int|null
	 */
	private ?int $size = null;

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
		$this->readable = isset(STREAM_MODES_READ[$meta['mode']]);
		$this->writable = isset(STREAM_MODES_WRITE[$meta['mode']]);
		$this->uri      = $meta['uri'] ?? null;
	}

	/**
	 * @inheritDoc
	 */
	public function __toString(){

		if(!is_resource($this->stream)){
			return '';
		}

		try{

			if($this->isSeekable()){
				$this->seek(0);
			}

			return (string)stream_get_contents($this->stream);
		}
		// @codeCoverageIgnoreStart
		catch(Exception $e){
			// https://bugs.php.net/bug.php?id=53648
			// @todo: fixed in 7.4
			trigger_error('Stream::__toString exception: '.$e->getMessage(), E_USER_ERROR);

			return '';
		}
		// @codeCoverageIgnoreEnd

	}

	/**
	 * @inheritDoc
	 */
	public function close():void{

		if(is_resource($this->stream)){
			fclose($this->stream);
		}

		$this->detach();
	}

	/**
	 * @inheritDoc
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
	 * @inheritDoc
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

		return null; // @codeCoverageIgnore
	}

	/**
	 * @inheritDoc
	 */
	public function tell():int{
		$result = ftell($this->stream);

		if($result === false){
			throw new RuntimeException('Unable to determine stream position'); // @codeCoverageIgnore
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function eof():bool{
		return !$this->stream || feof($this->stream);
	}

	/**
	 * @inheritDoc
	 */
	public function isSeekable():bool{
		return $this->seekable;
	}

	/**
	 * @inheritDoc
	 */
	public function seek($offset, $whence = SEEK_SET):void{

		if(!$this->seekable){
			throw new RuntimeException('Stream is not seekable');
		}
		elseif(fseek($this->stream, $offset, $whence) === -1){
			throw new RuntimeException('Unable to seek to stream position '.$offset.' with whence '.$whence);
		}

	}

	/**
	 * @inheritDoc
	 */
	public function rewind():void{
		$this->seek(0);
	}

	/**
	 * @inheritDoc
	 */
	public function isWritable():bool{
		return $this->writable;
	}

	/**
	 * @inheritDoc
	 */
	public function write($string):int{

		if(!$this->writable){
			throw new RuntimeException('Cannot write to a non-writable stream');
		}

		// We can't know the size after writing anything
		$this->size = null;
		$result     = fwrite($this->stream, $string);

		if($result === false){
			throw new RuntimeException('Unable to write to stream'); // @codeCoverageIgnore
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function isReadable():bool{
		return $this->readable;
	}

	/**
	 * @inheritDoc
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
			throw new RuntimeException('Unable to read from stream'); // @codeCoverageIgnore
		}

		return $string;
	}

	/**
	 * @inheritDoc
	 */
	public function getContents():string{
		$contents = stream_get_contents($this->stream);

		if($contents === false){
			throw new RuntimeException('Unable to read stream contents'); // @codeCoverageIgnore
		}

		return $contents;
	}

	/**
	 * @inheritDoc
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

}
