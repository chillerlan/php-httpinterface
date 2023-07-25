<?php
/**
 * Class MultipartStreamBuilder
 *
 * @created      19.07.2023
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2023 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Common;

use chillerlan\HTTP\Psr17\StreamFactory;
use chillerlan\HTTP\Psr7\Message;
use chillerlan\HTTP\Utils\{HeaderUtil, MessageUtil};
use InvalidArgumentException;
use Psr\Http\Message\{MessageInterface, StreamFactoryInterface, StreamInterface};
use function basename, count, implode, ksort, preg_match, random_bytes, sha1, sprintf, str_starts_with, trim;

/**
 * Use PSR-7 MessageInterface to build multipart messages
 *
 * @link https://datatracker.ietf.org/doc/html/rfc2046#section-5.1
 */
class MultipartStreamBuilder{

	protected StreamFactoryInterface $streamFactory;
	protected StreamInterface        $stream;
	/** @var \Psr\Http\Message\MessageInterface[]  */
	protected array                  $messages = [];
	protected string                 $boundary = '';

	/**
	 * MultipartStreamBuilder constructor
	 */
	public function __construct(StreamFactoryInterface $streamFactory = null){
		$this->streamFactory = $streamFactory ?? new StreamFactory;

		$this->reset();
	}

	/**
	 * Returns the stream content (make sure to save the boundary before!)
	 */
	public function __toString():string{
		return $this->build()->getContents();
	}

	/**
	 * Clears the MessageInterface array
	 */
	public function reset():static{
		$this->messages = [];
		$this->boundary = $this->getRandomBoundary();

		return $this;
	}

	/**
	 * Sets a boundary string
	 *
	 * permitted characters: DIGIT ALPHA '()+_,-./:=?
	 *
	 * @see https://datatracker.ietf.org/doc/html/rfc2046#section-5.1.1
	 */
	public function setBoundary(string $boundary):static{
		$boundary = trim($boundary);

		if($boundary === ''){
			throw new InvalidArgumentException('The given boundary is empty');
		}

		if(!preg_match('#^[a-z\d\'()+_,-./:=?]+$#i', $boundary)){
			throw new InvalidArgumentException('The given boundary contains illegal characters');
		}

		$this->boundary = $boundary;

		return $this;
	}

	/**
	 * Returns the current boundary string
	 */
	public function getBoundary():string{
		return $this->boundary;
	}

	/**
	 * Generates a random boundary string
	 */
	protected function getRandomBoundary():string{
		return sha1(random_bytes(8192));
	}

	/**
	 * Adds a message with the given content
	 */
	public function addString(
		string   $content,
		string   $fieldname = null,
		string   $filename = null,
		iterable $headers = null
	):static{
		return $this->addStream($this->streamFactory->createStream($content), $fieldname, $filename, $headers);
	}

	/**
	 * Adds a StreamInterface
	 */
	public function addStream(
		StreamInterface $stream,
		string          $fieldname = null,
		string          $filename = null,
		iterable        $headers = null
	):static{
		$message = new Message;

		if($headers !== null){
			foreach($headers as $name => $value){
				$message = $message->withAddedHeader($name, $value);
			}
		}

		return $this->addMessage($message->withBody($stream), $fieldname, $filename);
	}

	/**
	 * Adds a MessageInterface
	 */
	public function addMessage(MessageInterface $message, string $fieldname = null, string $filename = null):static{

		// hmm, we don't have a content-type, let's see if we can guess one
		if(!$message->hasHeader('content-type')){
			// let it throw or ignore??
			$message = MessageUtil::setContentTypeHeader($message, $filename);
		}

		// set Content-Disposition
		$message = $this->setContentDispositionHeader($message, $fieldname, $filename);

		// set Content-Length
		$this->messages[] = MessageUtil::setContentLengthHeader($message);

		return $this;
	}

	/**
	 * Builds the multipart content from the given messages.
	 *
	 * If a MessageInterface is given, the body and content type header with the boundary will be set
	 * and the MessageInterface is returned; returns the StreamInterface with the content otherwise.
	 */
	public function build(MessageInterface $message = null):StreamInterface|MessageInterface{
		$this->stream = $this->streamFactory->createStream();

		foreach($this->messages as $part){
			// write boundary before each part
			$this->stream->write(sprintf("--%s\r\n", $this->boundary));
			// write content
			$this->writeHeaders($part->getHeaders());
			$this->writeBody($part->getBody());
		}

		// write final boundary
		$this->stream->write(sprintf("--%s--\r\n", $this->boundary));
		// rewind stream!!!
		$this->stream->rewind();

		// just return the stream
		if($message === null){
			return $this->stream;
		}

		// write a proper multipart header to the given message and add the body
		return $message
			->withHeader('Content-Type', sprintf('multipart/form-data; boundary="%s"', $this->boundary))
			->withBody($this->stream)
		;
	}

	/**
	 * Parses and writes the headers from the given message to the multipart stream
	 */
	protected function writeHeaders(iterable $headers):void{
		$headers = HeaderUtil::normalize($headers);
		// beautify
		ksort($headers);

		foreach($headers as $name => $value){
			// skip unwanted headers
			if(!str_starts_with($name, 'Content') && !str_starts_with($name, 'X-')){
				continue;
			}

			// special rule to suppress the content type header
			if($name === 'Content-Type' && $value === ''){
				continue;
			}

			// write "Key: Value" followed by a newline
			$this->stream->write(sprintf("%s: %s\r\n", $name, $value));
		}
		// end with newline
		$this->stream->write("\r\n");
	}

	/**
	 * Writes the content of the given StreamInterface to the multipart stream
	 */
	protected function writeBody(StreamInterface $body):void{

		// rewind!!!
		if($body->isSeekable()){
			$body->rewind();
		}

		// stream is readable? fine!
		if($body->isReadable()){
			while(!$body->eof()){
				$this->stream->write($body->read(1048576));
			}
		}
		// else attempt casting the stream to string (might throw)
		else{
			$this->stream->write((string)$body); // @codeCoverageIgnore
		}
		// end with newline
		$this->stream->write("\r\n");
	}

	/**
	 * Sets the "Content-Disposition" header in the given MessageInterface if a name and/or filename are given
	 *
	 * If the header was already set on the message, this one will be used unmodified.
	 */
	protected function setContentDispositionHeader(
		MessageInterface $message,
		?string          $fieldname,
		?string          $filename
	):MessageInterface{
		// oh, you already set the header? okay - at your own risk! bye
		if($message->hasHeader('Content-Disposition')){
			return $message;
		}

		$contentDisposition = ['form-data'];

		if($fieldname !== null){
			$fieldname = trim($fieldname);

			if($fieldname === ''){
				throw new InvalidArgumentException('Invalid form field name');
			}

			$contentDisposition[] = sprintf('name="%s"', $fieldname);
		}

		if($filename !== null){
			$contentDisposition[] = sprintf('filename="%s"', basename($filename));
		}

		if(count($contentDisposition) > 1){
			return $message->withHeader('Content-Disposition', implode('; ', $contentDisposition));
		}

		return $message;
	}

}
