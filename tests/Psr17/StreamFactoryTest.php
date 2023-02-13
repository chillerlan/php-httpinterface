<?php
/**
 * @author       http-factory-tests Contributors
 * @license      MIT
 * @link         https://github.com/http-interop/http-factory-tests
 *
 * @noinspection PhpUndefinedConstantInspection
 */

namespace chillerlan\HTTPTest\Psr17;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use function class_exists;
use function defined;
use function file_exists;
use function file_put_contents;
use function fopen;
use function fwrite;
use function rewind;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

class StreamFactoryTest extends TestCase{

	protected StreamFactoryInterface $streamFactory;
	protected static array           $tempFiles = [];

	public function setUp():void{

		if(!defined('STREAM_FACTORY') || !class_exists(STREAM_FACTORY)){
			$this::markTestSkipped('STREAM_FACTORY class name not provided');
		}

		$this->streamFactory = new (STREAM_FACTORY);
	}

	public static function tearDownAfterClass():void{
		foreach(self::$tempFiles as $tempFile){
			if(file_exists($tempFile)){
				unlink($tempFile);
			}
		}
	}

	protected function createTemporaryFile():string{
		$file = tempnam(sys_get_temp_dir(), 'http_factory_tests_');

		if($file === false){
			throw new RuntimeException('could not create temp file');
		}

		self::$tempFiles[] = $file;

		return $file;
	}

	protected function createTemporaryResource(string $content = null){
		$file     = $this->createTemporaryFile();
		$resource = fopen($file, 'r+');

		if($content){
			fwrite($resource, $content);
			rewind($resource);
		}

		return $resource;
	}

	public function testCreateStreamWithoutArgument():void{
		$stream = $this->streamFactory->createStream();

		$this::assertInstanceOf(StreamInterface::class, $stream);
		$this::assertSame('', (string)$stream);
	}

	public function testCreateStreamWithEmptyString():void{
		$string = '';

		$stream = $this->streamFactory->createStream($string);

		$this::assertSame($string, (string)$stream);
	}

	public function testCreateStreamWithASCIIString():void{
		$string = 'would you like some crumpets?';

		$stream = $this->streamFactory->createStream($string);

		$this::assertSame($string, (string)$stream);
	}

	public function testCreateStreamWithMultiByteMultiLineString():void{
		$string = "would you\r\nlike some\n\u{1F950}?";

		$stream = $this->streamFactory->createStream($string);

		$this::assertSame($string, (string)$stream);
	}

	public function testCreateStreamFromFile():void{
		$string   = 'would you like some crumpets?';
		$filename = $this->createTemporaryFile();

		file_put_contents($filename, $string);

		$stream = $this->streamFactory->createStreamFromFile($filename);

		$this::assertSame($string, (string)$stream);
	}

	public function testCreateStreamFromResource():void{
		$string   = 'would you like some crumpets?';
		$resource = $this->createTemporaryResource($string);

		$stream = $this->streamFactory->createStreamFromResource($resource);

		$this::assertSame($string, (string)$stream);
	}

	public function testCreateStreamFromNonExistingFile():void{
		$filename = $this->createTemporaryFile();
		unlink($filename);

		$this->expectException(RuntimeException::class);
		$stream = $this->streamFactory->createStreamFromFile($filename);
	}

	public function testCreateStreamFromInvalidFileName():void{
		$this->expectException(RuntimeException::class);
		$stream = $this->streamFactory->createStreamFromFile('');
	}

	public function testCreateStreamFromFileIsReadOnlyByDefault():void{
		$string   = 'would you like some crumpets?';
		$filename = $this->createTemporaryFile();

		$stream = $this->streamFactory->createStreamFromFile($filename);

		$this->expectException(RuntimeException::class);
		$stream->write($string);
	}

	public function testCreateStreamFromFileWithWriteOnlyMode():void{
		$filename = $this->createTemporaryFile();

		$stream = $this->streamFactory->createStreamFromFile($filename, 'w');

		$this->expectException(RuntimeException::class);
		$stream->read(1);
	}

	public function testCreateStreamFromFileWithNoMode():void{
		$filename = $this->createTemporaryFile();

		if(file_exists($filename)){
			unlink($filename);
		}

		$this->expectException(Exception::class);
		$stream = $this->streamFactory->createStreamFromFile($filename, '');
	}

	public function testCreateStreamFromFileWithInvalidMode():void{
		$filename = $this->createTemporaryFile();

		$this->expectException(Exception::class);
		$stream = $this->streamFactory->createStreamFromFile($filename, "\u{2620}");
	}

}
