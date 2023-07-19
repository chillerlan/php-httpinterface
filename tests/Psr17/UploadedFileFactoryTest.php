<?php
/**
 * @author       http-factory-tests Contributors
 * @license      MIT
 * @link         https://github.com/http-interop/http-factory-tests
 *
 * @noinspection PhpUndefinedConstantInspection
 */

namespace chillerlan\HTTPTest\Psr17;

use chillerlan\HTTPTest\FactoryTrait;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;
use function strlen;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_OK;

class UploadedFileFactoryTest extends TestCase{
	use FactoryTrait;

	protected function assertUploadedFile(
		UploadedFileInterface $file,
		string $content,
		int $size = null,
		int $error = null,
		string $clientFilename = null,
		string $clientMediaType = null
	){
		$this::assertInstanceOf(UploadedFileInterface::class, $file);
		$this::assertSame($content, (string)$file->getStream());
		$this::assertSame($size ?? strlen($content), $file->getSize());
		$this::assertSame($error ?? UPLOAD_ERR_OK, $file->getError());
		$this::assertSame($clientFilename, $file->getClientFilename());
		$this::assertSame($clientMediaType, $file->getClientMediaType());
	}

	public function testCreateUploadedFileWithClientFilenameAndMediaType():void{
		$content         = 'this is your capitan speaking';
		$upload          = $this->streamFactory->createStream($content);
		$error           = UPLOAD_ERR_OK;
		$clientFilename  = 'test.txt';
		$clientMediaType = 'text/plain';

		$file = $this->uploadedFileFactory->createUploadedFile($upload, null, $error, $clientFilename, $clientMediaType);

		$this::assertUploadedFile($file, $content, null, $error, $clientFilename, $clientMediaType);
	}

	public function testCreateUploadedFileWithError():void{
		$upload = $this->streamFactory->createStream('foobar');
		$error  = UPLOAD_ERR_NO_FILE;

		$file = $this->uploadedFileFactory->createUploadedFile($upload, null, $error);

		// Cannot use assertUploadedFile() here because the error prevents
		// fetching the content stream.
		$this::assertInstanceOf(UploadedFileInterface::class, $file);
		$this::assertSame($error, $file->getError());
	}

}
