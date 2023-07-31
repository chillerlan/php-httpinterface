<?php
/**
 * Class StreamTest
 *
 * @link https://github.com/guzzle/psr7/blob/4b981cdeb8c13d22a6c193554f8c686f53d5c958/tests/StreamTest.php
 *
 * @created      12.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\Stream;
use chillerlan\HTTPTest\FactoryTrait;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use function filesize;
use function fopen;
use function fwrite;

class StreamTest extends TestCase{
	use FactoryTrait;

	public function testConstructorThrowsExceptionOnInvalidArgument():void{
		$this->expectException(InvalidArgumentException::class);

		/** @noinspection PhpParamsInspection */
		new Stream(true);
	}

	public function testConstructorInitializesProperties():void{
		$stream = $this->streamFactory->createStream('data');

		$this::assertTrue($stream->isReadable());
		$this::assertTrue($stream->isWritable());
		$this::assertTrue($stream->isSeekable());
		$this::assertSame('php://temp', $stream->getMetadata('uri'));
		$this::assertIsArray($stream->getMetadata());
		$this::assertSame(4, $stream->getSize());
		$this::assertFalse($stream->eof());
		$stream->close();
	}

	public function testStreamClosesHandleOnDestruct():void{
		$handle = fopen('php://temp', 'r');
		$stream = new Stream($handle);
		unset($stream);
		$this::assertFalse(is_resource($handle));
	}

	public function testConvertsToString():void{
		$stream = $this->streamFactory->createStream('data');
		$this::assertSame('data', (string)$stream);
		$this::assertSame('data', (string)$stream);
		$stream->close();
	}

	public function testGetsContents():void{
		$stream = $this->streamFactory->createStream('data');
		$this::assertSame('', $stream->getContents());
		$stream->seek(0);
		$this::assertSame('data', $stream->getContents());
		$this::assertSame('', $stream->getContents());
	}

	public function testChecksEof():void{
		$stream = $this->streamFactory->createStream('data');
		$this::assertFalse($stream->eof());
		$stream->read(4);
		$this::assertTrue($stream->eof());
		$stream->close();
	}

	public function testGetSize():void{
		$size   = filesize(__FILE__);
		$handle = fopen(__FILE__, 'r');
		$stream = new Stream($handle);
		$this::assertSame($size, $stream->getSize());
		// Load from cache
		$this::assertSame($size, $stream->getSize());
		$stream->close();
	}

	public function testEnsuresSizeIsConsistent():void{
		$h = fopen('php://temp', 'w+');
		$this::assertSame(3, fwrite($h, 'foo'));
		$stream = new Stream($h);
		$this::assertSame(3, $stream->getSize());
		$this::assertSame(4, $stream->write('test'));
		$this::assertSame(7, $stream->getSize());
		$this::assertSame(7, $stream->getSize());
		$stream->close();
	}

	public function testProvidesStreamPosition():void{
		$handle = fopen('php://temp', 'w+');
		$stream = new Stream($handle);
		$this::assertSame(0, $stream->tell());
		$stream->write('foo');
		$this::assertSame(3, $stream->tell());
		$stream->seek(1);
		$this::assertSame(1, $stream->tell());
		$this::assertSame(ftell($handle), $stream->tell());
		$stream->close();
	}

	public function testCanDetachStream():void{
		$handle = fopen('php://temp', 'w+');
		$stream = new Stream($handle);
		$stream->write('foo');

		$this::assertTrue($stream->isReadable());
		$this::assertSame($handle, $stream->detach());

		$stream->detach();

		$this::assertFalse($stream->isReadable());
		$this::assertFalse($stream->isWritable());
		$this::assertFalse($stream->isSeekable());

		$throws = function(callable $fn) use ($stream){
			try{
				$fn($stream);
				$this::fail();
			}
			catch(Exception $e){}
		};

		$throws(function(StreamInterface $stream){$stream->read(10);});
		$throws(function(StreamInterface $stream){$stream->write('bar');});
		$throws(function(StreamInterface $stream){$stream->seek(10);});
		$throws(function(StreamInterface $stream){$stream->tell();});
		$throws(function(StreamInterface $stream){$stream->eof();});
		$throws(function(StreamInterface $stream){$stream->getSize();});
		$throws(function(StreamInterface $stream){$stream->getContents();});

		$this::assertSame('', (string)$stream);
		$stream->close();
	}

	public function testCloseClearProperties():void{
		$stream = $this->streamFactory->createStream();
		$stream->close();

		$this::assertFalse($stream->isSeekable());
		$this::assertFalse($stream->isReadable());
		$this::assertFalse($stream->isWritable());
		$this::assertNull($stream->getSize());
		$this::assertEmpty($stream->getMetadata());
	}

	public function testStreamReadingWithZeroLength():void{
		$stream = $this->streamFactory->createStream();

		$this::assertSame('', $stream->read(0));

		$stream->close();
	}

	public function testStreamReadingWithNegativeLength():void{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Length parameter cannot be negative');

		$stream = $this->streamFactory->createStream();
		$stream->read(-1);
	}

	public function testStreamSeekInvalidPosition():void{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Unable to seek to stream position -1 with whence 0');

		$stream = $this->streamFactory->createStream();
		$stream->seek(-1);
	}

}
