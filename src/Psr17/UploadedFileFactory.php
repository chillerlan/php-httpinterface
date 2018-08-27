<?php
/**
 * Class UploadedFileFactory
 *
 * @filesource   UploadedFileFactory.php
 * @created      27.08.2018
 * @package      chillerlan\HTTP\Psr17
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr17;

use chillerlan\HTTP\Psr7\UploadedFile;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\{UploadedFileFactoryInterface, UploadedFileInterface};

final class UploadedFileFactory implements UploadedFileFactoryInterface{

	/**
	 * Create a new uploaded file.
	 *
	 * If a size is not provided it will be determined by checking the size of
	 * the file.
	 *
	 * @see http://php.net/manual/features.file-upload.post-method.php
	 * @see http://php.net/manual/features.file-upload.errors.php
	 *
	 * @param StreamInterface $stream          Underlying stream representing the
	 *                                         uploaded file content.
	 * @param int             $size            in bytes
	 * @param int             $error           PHP file upload error
	 * @param string          $clientFilename  Filename as provided by the client, if any.
	 * @param string          $clientMediaType Media type as provided by the client, if any.
	 *
	 *
	 * @return \Psr\Http\Message\UploadedFileInterface|\chillerlan\HTTP\Psr7\UploadedFile
	 *
	 * @throws \InvalidArgumentException If the file resource is not readable.
	 */
	public function createUploadedFile(StreamInterface $stream, int $size = null, int $error = \UPLOAD_ERR_OK, string $clientFilename = null, string $clientMediaType = null):UploadedFileInterface{
		return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
	}

	/**
	 * Return an UploadedFile instance array.
	 *
	 * @param array $files A array which respect $_FILES structure
	 * @throws \InvalidArgumentException for unrecognized values
	 * @return array
	 */
	public function normalizeFiles(array $files):array{
		$normalized = [];

		foreach($files as $key => $value){

			if($value instanceof UploadedFileInterface){
				$normalized[$key] = $value;
			}
			elseif(is_array($value) && isset($value['tmp_name'])){
				$normalized[$key] = $this->createUploadedFileFromSpec($value);
			}
			elseif(is_array($value)){
				$normalized[$key] = $this->normalizeFiles($value);
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
	 * @return array|\Psr\Http\Message\UploadedFileInterface
	 */
	private function createUploadedFileFromSpec(array $value){

		if(is_array($value['tmp_name'])){
			return $this->normalizeNestedFileSpec($value);
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
	 * @return \Psr\Http\Message\UploadedFileInterface[]
	 */
	private function normalizeNestedFileSpec(array $files = []):array{
		$normalizedFiles = [];

		foreach(array_keys($files['tmp_name']) as $key){
			$spec = [
				'tmp_name' => $files['tmp_name'][$key],
				'size'     => $files['size'][$key],
				'error'    => $files['error'][$key],
				'name'     => $files['name'][$key],
				'type'     => $files['type'][$key],
			];

			$normalizedFiles[$key] = $this->createUploadedFileFromSpec($spec);
		}

		return $normalizedFiles;
	}

}
