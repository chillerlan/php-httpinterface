<?php
/**
 * Class Stream
 *
 * @created      11.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use chillerlan\HTTP\Psr17\FactoryHelpers;
use InvalidArgumentException, RuntimeException;
use Psr\Http\Message\StreamInterface;
use function clearstatcache, fclose, feof, fread, fstat, ftell, fwrite, in_array,
	is_resource, stream_get_contents, stream_get_meta_data;

use const SEEK_SET;

/**
 * @property resource|null $stream
 */
class Stream implements StreamInterface{

	/** @var resource|null */
	protected $stream = null;

	protected bool $seekable;

	protected bool $readable;

	protected bool $writable;

	protected ?string $uri = null;

	protected ?int $size = null;

	/**
	 * Stream constructor.
	 *
	 * @param resource $stream
	 */
	public function __construct($stream){

		if(!is_resource($stream)){
			throw new InvalidArgumentException('Stream must be a resource');
		}

		$this->stream   = $stream;
		$meta           = $this->getMetadata();
		$mode           = $meta['mode'] ?? null;
		$this->seekable = $meta['seekable'] ?? false;
		$this->readable = in_array($mode, FactoryHelpers::STREAM_MODES_READ);
		$this->writable = in_array($mode, FactoryHelpers::STREAM_MODES_WRITE);
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
	 * @inheritDoc
	 */
	public function __toString(){

		if(!is_resource($this->stream)){
			return '';
		}

		if($this->isSeekable()){
			$this->seek(0);
		}

		// this would be nice but some iplementations don't like nice things :(
#		$wrapper_type = $this->getMetadata('wrapper_type');
#		if($wrapper_type === 'plainfile'){
#			return $this->getMetadata('uri');
#		}

		return $this->getContents();
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

		if(!is_resource($this->stream)){
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

		if($this->size !== null){
			return $this->size;
		}

		return null; // @codeCoverageIgnore
	}

	/**
	 * @inheritDoc
	 */
	public function tell():int{

		if(!is_resource($this->stream)){
			throw new RuntimeException('Invalid stream'); // @codeCoverageIgnore
		}

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

		if(!is_resource($this->stream)){
			throw new RuntimeException('Invalid stream'); // @codeCoverageIgnore
		}

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

		if(!is_resource($this->stream)){
			throw new RuntimeException('Invalid stream'); // @codeCoverageIgnore
		}

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

		if(!is_resource($this->stream)){
			throw new RuntimeException('Invalid stream'); // @codeCoverageIgnore
		}

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

		if(!is_resource($this->stream)){
			throw new RuntimeException('Invalid stream'); // @codeCoverageIgnore
		}

		if(!$this->readable){
			throw new RuntimeException('Cannot read from non-readable stream');
		}

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

		if(!is_resource($this->stream)){
			return $key ? null : [];
		}
		elseif($key === null){
			return stream_get_meta_data($this->stream);
		}

		$meta = stream_get_meta_data($this->stream);

		return $meta[$key] ?? null;
	}

}
