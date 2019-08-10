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
use Psr\Http\Message\{StreamInterface, UploadedFileFactoryInterface, UploadedFileInterface};

use const UPLOAD_ERR_OK;

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
	public function createUploadedFile(StreamInterface $stream, int $size = null, int $error = UPLOAD_ERR_OK, string $clientFilename = null, string $clientMediaType = null):UploadedFileInterface{
		return new UploadedFile($stream, $size ?? $stream->getSize(), $error, $clientFilename, $clientMediaType);
	}

}
