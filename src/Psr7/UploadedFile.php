<?php
/**
 * Class UploadedFile
 *
 * @created      11.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use chillerlan\HTTP\Psr17\{FactoryHelpers, StreamFactory};
use Psr\Http\Message\{StreamInterface, UploadedFileInterface};
use InvalidArgumentException, RuntimeException;

use function in_array, is_file, is_string, is_writable, move_uploaded_file, php_sapi_name,rename;

use const UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION, UPLOAD_ERR_FORM_SIZE, UPLOAD_ERR_INI_SIZE,
	UPLOAD_ERR_NO_FILE, UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_OK, UPLOAD_ERR_PARTIAL;

class UploadedFile implements UploadedFileInterface{

	/** @var int[] */
	public const UPLOAD_ERRORS = [
		UPLOAD_ERR_OK,
		UPLOAD_ERR_INI_SIZE,
		UPLOAD_ERR_FORM_SIZE,
		UPLOAD_ERR_PARTIAL,
		UPLOAD_ERR_NO_FILE,
		UPLOAD_ERR_NO_TMP_DIR,
		UPLOAD_ERR_CANT_WRITE,
		UPLOAD_ERR_EXTENSION,
	];

	private int $error;

	private int $size;

	private ?string $clientFilename;

	private ?string $clientMediaType;

	private ?string $file = null;

	private ?StreamInterface $stream;

	private bool $moved = false;

	private StreamFactory $streamFactory;

	/**
	 * @param \Psr\Http\Message\StreamInterface|string|resource $file
	 * @param int                                               $size
	 * @param int                                               $error
	 * @param string|null                                       $filename
	 * @param string|null                                       $mediaType
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($file, int $size, int $error = UPLOAD_ERR_OK, string $filename = null, string $mediaType = null){

		if(!in_array($error, $this::UPLOAD_ERRORS, true)){
			throw new InvalidArgumentException('Invalid error status for UploadedFile');
		}

		$this->size            = (int)$size; // int type hint also accepts float...
		$this->error           = $error;
		$this->clientFilename  = $filename;
		$this->clientMediaType = $mediaType;
		$this->streamFactory   = new StreamFactory;

		if($this->error === UPLOAD_ERR_OK){

			is_string($file)
				? $this->file = $file
				: $this->stream = FactoryHelpers::create_stream_from_input($file);

		}

	}

	/**
	 * @inheritDoc
	 */
	public function getStream():StreamInterface{

		$this->validateActive();

		if($this->stream instanceof StreamInterface){
			return $this->stream;
		}

		if(is_file($this->file)){
			return $this->streamFactory->createStreamFromFile($this->file, 'r+');
		}

		return $this->streamFactory->createStream($this->file);
	}

	/**
	 * @inheritDoc
	 */
	public function moveTo($targetPath):void{

		$this->validateActive();

		if(is_string($targetPath) && empty($targetPath)){
			throw new InvalidArgumentException('Invalid path provided for move operation; must be a non-empty string');
		}

		if(!is_writable($targetPath)){
			throw new RuntimeException('Directory is not writable: '.$targetPath);
		}

		if($this->file !== null){
			$this->moved = php_sapi_name() === 'cli'
				? rename($this->file, $targetPath)
				: move_uploaded_file($this->file, $targetPath);
		}
		else{
			$this->copyToStream($this->streamFactory->createStreamFromFile($targetPath, 'r+'));
			$this->moved = true;
		}

		if($this->moved === false){
			throw new RuntimeException('Uploaded file could not be moved to '.$targetPath); // @codeCoverageIgnore
		}

	}

	/**
	 * @inheritDoc
	 */
	public function getSize():?int{
		return $this->size;
	}

	/**
	 * @inheritDoc
	 */
	public function getError():int{
		return $this->error;
	}

	/**
	 * @inheritDoc
	 */
	public function getClientFilename():?string{
		return $this->clientFilename;
	}

	/**
	 * @inheritDoc
	 */
	public function getClientMediaType():?string{
		return $this->clientMediaType;
	}

	/**
	 * @throws RuntimeException if is moved or not ok
	 */
	private function validateActive():void{

		if($this->error !== UPLOAD_ERR_OK){
			throw new RuntimeException('Cannot retrieve stream due to upload error');
		}

		if($this->moved){
			throw new RuntimeException('Cannot retrieve stream after it has already been moved');
		}

	}

	/**
	 * Copy the contents of a stream into another stream until the given number
	 * of bytes have been read.
	 *
	 * @author Michael Dowling and contributors to guzzlehttp/psr7
	 *
	 * @param StreamInterface $dest   Stream to write to
	 *
	 * @throws \RuntimeException on error
	 */
	private function copyToStream(StreamInterface $dest){
		$source = $this->getStream();

		if($source->isSeekable()){
			$source->rewind();
		}

		while(!$source->eof()){

			if(!$dest->write($source->read(1048576))){
				break; // @codeCoverageIgnore
			}

		}

	}

}
