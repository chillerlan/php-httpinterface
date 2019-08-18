<?php
/**
 * Class StreamTest
 *
 * @link https://github.com/guzzle/psr7/blob/4b981cdeb8c13d22a6c193554f8c686f53d5c958/tests/StreamTest.php
 *
 * @filesource   StreamTest.php
 * @created      12.08.2018
 * @package      chillerlan\HTTPTest\Psr7
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\Stream;
use Psr\Http\Message\StreamInterface;
use PHPUnit\Framework\TestCase;
use Exception, InvalidArgumentException, RuntimeException;

use function chillerlan\HTTP\Psr17\create_stream;

class StreamTest extends TestCase{

	public function testConstructorThrowsExceptionOnInvalidArgument(){
		$this->expectException(InvalidArgumentException::class);

		/** @noinspection PhpParamsInspection */
		new Stream(true);
	}

	public function testConstructorInitializesProperties(){
		$stream = create_stream('data');

		$this->assertTrue($stream->isReadable());
		$this->assertTrue($stream->isWritable());
		$this->assertTrue($stream->isSeekable());
		$this->assertEquals('php://temp', $stream->getMetadata('uri'));
		$this->assertIsArray($stream->getMetadata());
		$this->assertEquals(4, $stream->getSize());
		$this->assertFalse($stream->eof());
		$stream->close();
	}

	public function testStreamClosesHandleOnDestruct(){
		$handle = fopen('php://temp', 'r');
		$stream = new Stream($handle);
		unset($stream);
		$this->assertFalse(is_resource($handle));
	}

	public function testConvertsToString(){
		$stream = create_stream('data', 'w+', false);
		$this->assertEquals('data', (string)$stream);
		$this->assertEquals('data', (string)$stream);
		$stream->close();
	}

	public function testGetsContents(){
		$stream = create_stream('data', 'w+', false);
		$this->assertEquals('', $stream->getContents());
		$stream->seek(0);
		$this->assertEquals('data', $stream->getContents());
		$this->assertEquals('', $stream->getContents());
	}

	public function testChecksEof(){
		$stream = create_stream('data', 'w+', false);
		$this->assertFalse($stream->eof());
		$stream->read(4);
		$this->assertTrue($stream->eof());
		$stream->close();
	}

	public function testGetSize(){
		$size   = filesize(__FILE__);
		$handle = fopen(__FILE__, 'r');
		$stream = new Stream($handle);
		$this->assertEquals($size, $stream->getSize());
		// Load from cache
		$this->assertEquals($size, $stream->getSize());
		$stream->close();
	}

	public function testEnsuresSizeIsConsistent(){
		$h = fopen('php://temp', 'w+');
		$this->assertEquals(3, fwrite($h, 'foo'));
		$stream = new Stream($h);
		$this->assertEquals(3, $stream->getSize());
		$this->assertEquals(4, $stream->write('test'));
		$this->assertEquals(7, $stream->getSize());
		$this->assertEquals(7, $stream->getSize());
		$stream->close();
	}

	public function testProvidesStreamPosition(){
		$handle = fopen('php://temp', 'w+');
		$stream = new Stream($handle);
		$this->assertEquals(0, $stream->tell());
		$stream->write('foo');
		$this->assertEquals(3, $stream->tell());
		$stream->seek(1);
		$this->assertEquals(1, $stream->tell());
		$this->assertSame(ftell($handle), $stream->tell());
		$stream->close();
	}

	public function testCanDetachStream(){
		$handle = fopen('php://temp', 'w+');
		$stream = new Stream($handle);
		$stream->write('foo');

		$this->assertTrue($stream->isReadable());
		$this->assertSame($handle, $stream->detach());

		$stream->detach();

		$this->assertFalse($stream->isReadable());
		$this->assertFalse($stream->isWritable());
		$this->assertFalse($stream->isSeekable());

		$throws = function(callable $fn) use ($stream){
			try{
				$fn($stream);
				$this->fail();
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

		$this->assertSame('', (string)$stream);
		$stream->close();
	}

	public function testCloseClearProperties(){
		$stream = create_stream();
		$stream->close();

		$this->assertFalse($stream->isSeekable());
		$this->assertFalse($stream->isReadable());
		$this->assertFalse($stream->isWritable());
		$this->assertNull($stream->getSize());
		$this->assertEmpty($stream->getMetadata());
	}

	public function testStreamReadingWithZeroLength(){
		$stream = create_stream();

		$this->assertSame('', $stream->read(0));

		$stream->close();
	}

	public function testStreamReadingWithNegativeLength(){
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Length parameter cannot be negative');

		$stream = create_stream();
		$stream->read(-1);
	}

	public function testStreamSeekInvalidPosition(){
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Unable to seek to stream position -1 with whence 0');

		$stream = create_stream();
		$stream->seek(-1);
	}

}
