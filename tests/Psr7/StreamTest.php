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
use chillerlan\HTTP\Psr17\StreamFactory;
use Exception;
use Psr\Http\Message\StreamInterface;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase{

	/**
	 * @var \chillerlan\HTTP\Psr17\StreamFactory
	 */
	protected $streamFactory;

	protected function setUp(){
		$this->streamFactory   = new StreamFactory;
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testConstructorThrowsExceptionOnInvalidArgument(){
		/** @noinspection PhpParamsInspection */
		new Stream(true);
	}

	public function testConstructorInitializesProperties(){
		$handle = fopen('php://temp', 'r+');
		fwrite($handle, 'data');
		$stream = $this->streamFactory->createStreamFromResource($handle); // HTTPFactory coverage

		$this->assertTrue($stream->isReadable());
		$this->assertTrue($stream->isWritable());
		$this->assertTrue($stream->isSeekable());
		$this->assertEquals('php://temp', $stream->getMetadata('uri'));
		$this->assertInternalType('array', $stream->getMetadata());
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
		$handle = fopen('php://temp', 'w+');
		fwrite($handle, 'data');
		$stream = new Stream($handle);
		$this->assertEquals('data', (string)$stream);
		$this->assertEquals('data', (string)$stream);
		$stream->close();
	}

	public function testGetsContents(){
		$handle = fopen('php://temp', 'w+');
		fwrite($handle, 'data');
		$stream = new Stream($handle);
		$this->assertEquals('', $stream->getContents());
		$stream->seek(0);
		$this->assertEquals('data', $stream->getContents());
		$this->assertEquals('', $stream->getContents());
	}

	public function testChecksEof(){
		$handle = fopen('php://temp', 'w+');
		fwrite($handle, 'data');
		$stream = new Stream($handle);
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
		$r      = fopen('php://temp', 'w+');
		$stream = new Stream($r);
		$stream->write('foo');

		$this->assertTrue($stream->isReadable());
		$this->assertSame($r, $stream->detach());

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
		$handle = fopen('php://temp', 'r+');
		$stream = new Stream($handle);
		$stream->close();

		$this->assertFalse($stream->isSeekable());
		$this->assertFalse($stream->isReadable());
		$this->assertFalse($stream->isWritable());
		$this->assertNull($stream->getSize());
		$this->assertEmpty($stream->getMetadata());
	}

	public function testStreamReadingWithZeroLength(){
		$r      = fopen('php://temp', 'r');
		$stream = new Stream($r);

		$this->assertSame('', $stream->read(0));

		$stream->close();
	}

	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Length parameter cannot be negative
	 */
	public function testStreamReadingWithNegativeLength(){
		$r      = fopen('php://temp', 'r');
		$stream = new Stream($r);

		try{
			$stream->read(-1);
		}
		catch(\Exception $e){
			$stream->close();
			/** @noinspection PhpUnhandledExceptionInspection */
			throw $e;
		}

		$stream->close();
	}

}