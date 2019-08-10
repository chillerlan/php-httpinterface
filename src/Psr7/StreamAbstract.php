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

	public const MODES_READ = [
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

	public const MODES_WRITE = [
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
	 * @var \Psr\Http\Message\StreamInterface
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
	 * @inheritdoc
	 * @codeCoverageIgnore
	 */
	public function __toString(){
		return (string)$this->stream;
	}

	/**
	 * @inheritdoc
	 */
	public function close(){
		if($this->stream instanceof StreamInterface){
			$this->stream->close();
		}
	}

	/**
	 * @inheritdoc
	 * @codeCoverageIgnore
	 */
	public function detach(){
		return $this->stream->detach();
	}

	/**
	 * @inheritdoc
	 * @codeCoverageIgnore
	 */
	public function getSize():?int{
		return $this->stream->getSize();
	}

	/**
	 * @inheritdoc
	 * @codeCoverageIgnore
	 */
	public function tell():int{
		return $this->stream->tell();
	}

	/**
	 * @inheritdoc
	 * @codeCoverageIgnore
	 */
	public function eof():bool{
		return $this->stream->eof();
	}

	/**
	 * @inheritdoc
	 * @codeCoverageIgnore
	 */
	public function isSeekable():bool{
		return $this->stream->isSeekable();
	}

	/**
	 * @inheritdoc
	 * @codeCoverageIgnore
	 */
	public function seek($offset, $whence = SEEK_SET):void{
		$this->stream->seek($offset, $whence);
	}

	/**
	 * @inheritdoc
	 * @codeCoverageIgnore
	 */
	public function rewind():void{
		$this->stream->rewind();
	}

	/**
	 * @inheritdoc
	 * @codeCoverageIgnore
	 */
	public function isWritable():bool{
		return $this->stream->isWritable();
	}

	/**
	 * @inheritdoc
	 * @codeCoverageIgnore
	 */
	public function write($string):int{
		return $this->stream->write($string);
	}

	/**
	 * @inheritdoc
	 * @codeCoverageIgnore
	 */
	public function isReadable():bool{
		return $this->stream->isReadable();
	}

	/**
	 * @inheritdoc
	 * @codeCoverageIgnore
	 */
	public function read($length):string{
		return $this->stream->read($length);
	}

	/**
	 * @inheritdoc
	 * @codeCoverageIgnore
	 */
	public function getContents():string{
		return $this->stream->getContents();
	}

	/**
	 * @inheritdoc
	 * @codeCoverageIgnore
	 */
	public function getMetadata($key = null){
		return $this->stream->getMetadata($key);
	}

}
