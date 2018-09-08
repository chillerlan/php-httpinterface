<?php
/**
 * Class UploadedFile
 *
 * @filesource   UploadedFile.php
 * @created      11.08.2018
 * @package      chillerlan\HTTP\Psr7
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use chillerlan\HTTP\{Psr17, Psr17\StreamFactory};
use Psr\Http\Message\{StreamInterface, UploadedFileInterface};
use InvalidArgumentException, RuntimeException;

final class UploadedFile implements UploadedFileInterface{

	/**
	 * @var int[]
	 */
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

	/**
	 * @var int
	 */
	private $error;

	/**
	 * @var int
	 */
	private $size;

	/**
	 * @var null|string
	 */
	private $clientFilename;

	/**
	 * @var null|string
	 */
	private $clientMediaType;

	/**
	 * @var null|string
	 */
	private $file;

	/**
	 * @var null|\Psr\Http\Message\StreamInterface
	 */
	private $stream;

	/**
	 * @var bool
	 */
	private $moved = false;

	/**
	 * @var \chillerlan\HTTP\Psr17\StreamFactory
	 */
	protected $streamFactory;

	/**
	 * @param \Psr\Http\Message\StreamInterface|string|resource $file
	 * @param int                                               $size
	 * @param int                                               $error
	 * @param string|null                                       $filename
	 * @param string|null                                       $mediaType
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct($file, int $size, int $error, string $filename = null, string $mediaType = null){

		if(!in_array($error, $this::UPLOAD_ERRORS, true)){
			throw new InvalidArgumentException('Invalid error status for UploadedFile');
		}

		$this->size            = (int)$size; // int type hint also accepts float...
		$this->error           = $error;
		$this->clientFilename  = $filename;
		$this->clientMediaType = $mediaType;
		$this->streamFactory   = new StreamFactory;

		if($this->error === UPLOAD_ERR_OK){

			if(is_string($file)){
				$this->file = $file;
			}
			else{
				$this->stream = Psr17\create_stream_from_input($file);
			}

		}

	}

	/**
	 * @inheritdoc
	 */
	public function getStream():StreamInterface{

		$this->validateActive();

		if($this->stream instanceof StreamInterface){
			return $this->stream;
		}

		return $this->streamFactory->createStreamFromFile($this->file, 'r+');
	}

	/**
	 * @inheritdoc
	 */
	public function moveTo($targetPath):void{

		$this->validateActive();

		if(is_string($targetPath) && empty($targetPath)){
			throw new InvalidArgumentException('Invalid path provided for move operation; must be a non-empty string');
		}

		if(!is_writable($targetPath)){
			throw new RuntimeException(sprintf('Directory %s is not writable', $targetPath));
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
			throw new RuntimeException(sprintf('Uploaded file could not be moved to %s', $targetPath));
		}

	}

	/**
	 * @inheritdoc
	 */
	public function getSize():?int{
		return $this->size;
	}

	/**
	 * @inheritdoc
	 */
	public function getError():int{
		return $this->error;
	}

	/**
	 * @inheritdoc
	 */
	public function getClientFilename():?string{
		return $this->clientFilename;
	}

	/**
	 * @inheritdoc
	 */
	public function getClientMediaType():?string{
		return $this->clientMediaType;
	}

	/**
	 * @return void
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
				break;
			}

		}

	}

}
