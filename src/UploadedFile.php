<?php
/**
 * Class UploadedFile
 *
 * @filesource   UploadedFile.php
 * @created      11.08.2018
 * @package      chillerlan\HTTP
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

use InvalidArgumentException;
use Psr\Http\Message\{StreamInterface, UploadedFileInterface};
use RuntimeException;

final class UploadedFile implements UploadedFileInterface{

	/**
	 * @var int[]
	 */
	private const UPLOAD_ERRORS = [
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

		if($this->error === UPLOAD_ERR_OK){

			if(is_string($file)){
				$this->file = $file;
			}
			else{
				$this->stream = Stream::fromInputGuess($file);
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

		return new Stream(fopen($this->file, 'r+'));
	}

	/**
	 * @inheritdoc
	 */
	public function moveTo($targetPath):void{

		$this->validateActive();

		if(is_string($targetPath) && empty($targetPath)){
			throw new InvalidArgumentException('Invalid path provided for move operation; must be a non-empty string');
		}

		if($this->file !== null){
			$this->moved = php_sapi_name() === 'cli'
				? rename($this->file, $targetPath)
				: move_uploaded_file($this->file, $targetPath);
		}
		else{
			$this->copyToStream(new Stream(fopen($targetPath, 'r+')));
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
	 * Return an UploadedFile instance array.
	 *
	 * @param array $files A array which respect $_FILES structure
	 * @throws InvalidArgumentException for unrecognized values
	 * @return array
	 */
	public static function normalizeFiles(array $files){
		$normalized = [];

		foreach($files as $key => $value){

			if($value instanceof UploadedFileInterface){
				$normalized[$key] = $value;
			}
			elseif(is_array($value) && isset($value['tmp_name'])){
				$normalized[$key] = self::createUploadedFileFromSpec($value);
			}
			elseif(is_array($value)){
				$normalized[$key] = self::normalizeFiles($value);
				continue;
			}
			else{
				throw new InvalidArgumentException('Invalid value in files specification');
			}

		}

		return $normalized;
	}

	/**
	 * Create and return an UploadedFile instance from a $_FILES specification.
	 *
	 * If the specification represents an array of values, this method will
	 * delegate to normalizeNestedFileSpec() and return that return value.
	 *
	 * @param array $value $_FILES struct
	 * @return array|UploadedFileInterface
	 */
	private static function createUploadedFileFromSpec(array $value){

		if(is_array($value['tmp_name'])){
			return self::normalizeNestedFileSpec($value);
		}

		return new UploadedFile($value['tmp_name'], (int)$value['size'], (int)$value['error'], $value['name'], $value['type']);
	}

	/**
	 * Normalize an array of file specifications.
	 *
	 * Loops through all nested files and returns a normalized array of
	 * UploadedFileInterface instances.
	 *
	 * @param array $files
	 * @return UploadedFileInterface[]
	 */
	private static function normalizeNestedFileSpec(array $files = []):array{
		$normalizedFiles = [];

		foreach(array_keys($files['tmp_name']) as $key){
			$spec = [
				'tmp_name' => $files['tmp_name'][$key],
				'size'     => $files['size'][$key],
				'error'    => $files['error'][$key],
				'name'     => $files['name'][$key],
				'type'     => $files['type'][$key],
			];

			$normalizedFiles[$key] = self::createUploadedFileFromSpec($spec);
		}

		return $normalizedFiles;
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
