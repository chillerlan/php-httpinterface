<?php
/**
 * Class StreamAbstract
 *
 * @filesource   StreamAbstract.php
 * @created      21.12.2018
 * @package      chillerlan\HTTP\Psr7
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use Psr\Http\Message\StreamInterface;

use const SEEK_SET;

abstract class StreamAbstract implements StreamInterface{

	/**
	 * @var \Psr\Http\Message\StreamInterface|resource
	 */
	protected $stream;

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
	 * @codeCoverageIgnore
	 */
	public function __toString(){
		return (string)$this->stream;
	}

	/**
	 * @inheritDoc
	 */
	public function close(){
		if($this->stream instanceof StreamInterface){
			$this->stream->close();
		}
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function detach(){
		return $this->stream->detach();
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function getSize():?int{
		return $this->stream->getSize();
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function tell():int{
		return $this->stream->tell();
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function eof():bool{
		return $this->stream->eof();
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function isSeekable():bool{
		return $this->stream->isSeekable();
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function seek($offset, $whence = SEEK_SET):void{
		$this->stream->seek($offset, $whence);
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function rewind():void{
		$this->stream->rewind();
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function isWritable():bool{
		return $this->stream->isWritable();
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function write($string):int{
		return $this->stream->write($string);
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function isReadable():bool{
		return $this->stream->isReadable();
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function read($length):string{
		return $this->stream->read($length);
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function getContents():string{
		return $this->stream->getContents();
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function getMetadata($key = null){
		return $this->stream->getMetadata($key);
	}

}
