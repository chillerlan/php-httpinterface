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
	 * @inheritDoc
	 */
	public function createUploadedFile(StreamInterface $stream, int $size = null, int $error = UPLOAD_ERR_OK, string $clientFilename = null, string $clientMediaType = null):UploadedFileInterface{
		return new UploadedFile($stream, $size ?? $stream->getSize(), $error, $clientFilename, $clientMediaType);
	}

}
