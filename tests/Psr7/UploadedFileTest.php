<?php
/**
 * Class UploadedFileTest
 *
 * @link         https://github.com/guzzle/psr7/blob/4b981cdeb8c13d22a6c193554f8c686f53d5c958/tests/UploadedFileTest.php
 *
 * @created      12.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\UploadedFile;
use chillerlan\HTTPTest\FactoryTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException, RuntimeException;
use function basename, file_exists, fopen, is_scalar, sys_get_temp_dir, tempnam, uniqid, unlink;
use const PHP_OS_FAMILY, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION, UPLOAD_ERR_FORM_SIZE,
	UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_NO_FILE, UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_OK, UPLOAD_ERR_PARTIAL;

class UploadedFileTest extends TestCase{
	use FactoryTrait;

	protected array $cleanup;

	// called from FactoryTrait
	protected function __setUp():void{
		$this->cleanup = [];
	}

	protected function tearDown():void{
		foreach($this->cleanup as $file){
			if(is_scalar($file) && file_exists($file)){
				unlink($file);
			}
		}
	}

	public static function invalidStreams():array{
		return [
#			'null'   => [null],
#			'true'   => [true],
#			'false'  => [false],
#			'int'    => [1],
#			'float'  => [1.1],
			'array'  => [['filename']],
			'object' => [(object)['filename']],
		];
	}

	#[DataProvider('invalidStreams')]
	public function testRaisesExceptionOnInvalidStreamOrFile(mixed $streamOrFile){
		$this->expectException(InvalidArgumentException::class);

		new UploadedFile($streamOrFile, 0);
	}

	public static function invalidErrorStatuses():array{
		return [
			'negative' => [-1],
			'too-big'  => [9],
		];
	}

	#[DataProvider('invalidErrorStatuses')]
	public function testRaisesExceptionOnInvalidErrorStatus(int $status):void{
		$this->expectException(InvalidArgumentException::class);

		new UploadedFile(fopen('php://temp', 'wb+'), 0, $status);
	}

	public function testGetStreamReturnsOriginalStreamObject():void{
		$stream = $this->streamFactory->createStream();
		$upload = new UploadedFile($stream, 0);

		$this::assertSame($stream, $upload->getStream());
	}

	public function testGetStreamReturnsWrappedPhpStream():void{
		$stream       = fopen('php://temp', 'wb+');
		$upload       = new UploadedFile($stream, 0);
		$uploadStream = $upload->getStream()->detach();

		$this::assertSame($stream, $uploadStream);
	}

	public function testSuccessful():void{
		$stream = $this->streamFactory->createStream('Foo bar!');
		$upload = new UploadedFile($stream, $stream->getSize(), UPLOAD_ERR_OK, 'filename.txt', 'text/plain');

		$this::assertSame($stream->getSize(), $upload->getSize());
		$this::assertSame('filename.txt', $upload->getClientFilename());
		$this::assertSame('text/plain', $upload->getClientMediaType());

		$to              = tempnam(sys_get_temp_dir(), 'successful');
		$this->cleanup[] = $to;
		$upload->moveTo($to);
		$this::assertFileExists($to);
		$this::assertSame($stream->__toString(), file_get_contents($to));
	}

	public function testMoveCannotBeCalledMoreThanOnce():void{
		$stream = $this->streamFactory->createStream('Foo bar!');
		$upload = new UploadedFile($stream, 0);

		$to              = tempnam(sys_get_temp_dir(), 'diac');
		$this->cleanup[] = $to;
		$upload->moveTo($to);
		$this::assertTrue(file_exists($to));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Cannot retrieve stream after it has already been moved');
		$upload->moveTo($to);
	}

	public function testCannotRetrieveStreamAfterMove():void{
		$stream = $this->streamFactory->createStream('Foo bar!');
		$upload = new UploadedFile($stream, 0);

		$to              = tempnam(sys_get_temp_dir(), 'diac');
		$this->cleanup[] = $to;
		$upload->moveTo($to);
		$this::assertFileExists($to);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Cannot retrieve stream after it has already been moved');
		$upload->getStream();
	}

	public function testCannotMoveToEmptyTarget():void{
		$stream = $this->streamFactory->createStream('Foo bar!');
		$upload = new UploadedFile($stream, 0);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid path provided for move operation; must be a non-empty string');
		$upload->moveTo('');
	}

	public function testCannotMoveToUnwritableDirectory():void{

		if(PHP_OS_FAMILY !== 'Linux'){
			$this->markTestSkipped('testing Linux only');
		}

		$stream = $this->streamFactory->createStream('Foo bar!');
		$upload = new UploadedFile($stream, 0);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Directory is not writable');
		$upload->moveTo('/boot');
	}

	public static function nonOkErrorStatus():array{
		return [
			'UPLOAD_ERR_INI_SIZE'   => [UPLOAD_ERR_INI_SIZE],
			'UPLOAD_ERR_FORM_SIZE'  => [UPLOAD_ERR_FORM_SIZE],
			'UPLOAD_ERR_PARTIAL'    => [UPLOAD_ERR_PARTIAL],
			'UPLOAD_ERR_NO_FILE'    => [UPLOAD_ERR_NO_FILE],
			'UPLOAD_ERR_NO_TMP_DIR' => [UPLOAD_ERR_NO_TMP_DIR],
			'UPLOAD_ERR_CANT_WRITE' => [UPLOAD_ERR_CANT_WRITE],
			'UPLOAD_ERR_EXTENSION'  => [UPLOAD_ERR_EXTENSION],
		];
	}

	#[DataProvider('nonOkErrorStatus')]
	public function testConstructorDoesNotRaiseExceptionForInvalidStreamWhenErrorStatusPresent(int $status):void{
		$uploadedFile = new UploadedFile('not ok', 0, $status);
		$this::assertSame($status, $uploadedFile->getError());
	}

	#[DataProvider('nonOkErrorStatus')]
	public function testMoveToRaisesExceptionWhenErrorStatusPresent(int $status):void{
		$uploadedFile = new UploadedFile('not ok', 0, $status);
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Cannot retrieve stream due to upload error');
		$uploadedFile->moveTo(__DIR__.'/'.uniqid());
	}

	#[DataProvider('nonOkErrorStatus')]
	public function testGetStreamRaisesExceptionWhenErrorStatusPresent(int $status):void{
		$uploadedFile = new UploadedFile('not ok', 0, $status);
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Cannot retrieve stream due to upload error');
		$uploadedFile->getStream();
	}

	public function testMoveToCreatesStreamIfOnlyAFilenameWasProvided():void{
		$from = tempnam(sys_get_temp_dir(), 'copy_from');
		$to   = tempnam(sys_get_temp_dir(), 'copy_to');

		$this->cleanup[] = $from;
		$this->cleanup[] = $to;

		copy(__FILE__, $from);

		$uploadedFile = new UploadedFile($from, 100, UPLOAD_ERR_OK, basename($from), 'text/plain');
		// why does this produce an error under windows when running with coverage???
		$uploadedFile->moveTo($to);

		$this::assertFileEquals(__FILE__, $to);
	}

	public function testNormalizeFilesRaisesException():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid value in files specification');

		$this->server->normalizeFiles(['test' => 'something']);
	}

}
