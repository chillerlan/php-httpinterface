<?php
/**
 * @author       http-factory-tests Contributors
 * @license      MIT
 * @link         https://github.com/http-interop/http-factory-tests
 *
 * @noinspection PhpUndefinedConstantInspection
 */

namespace chillerlan\HTTPTest\Psr17;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use function class_exists;
use function defined;
use function strlen;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_OK;

class UploadedFileFactoryTest extends TestCase{

	protected UploadedFileFactoryInterface $uploadedFileFactory;
	protected StreamFactoryInterface       $streamFactory;

	public function setUp():void{

		if(!defined('UPLOADED_FILE_FACTORY') || !class_exists(UPLOADED_FILE_FACTORY)){
			$this::markTestSkipped('UPLOADED_FILE_FACTORY class name not provided');
		}

		if(!defined('STREAM_FACTORY') || !class_exists(STREAM_FACTORY)){
			$this::markTestSkipped('STREAM_FACTORY class name not provided');
		}

		$this->uploadedFileFactory = new (UPLOADED_FILE_FACTORY);
		$this->streamFactory       = new (STREAM_FACTORY);
	}

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
