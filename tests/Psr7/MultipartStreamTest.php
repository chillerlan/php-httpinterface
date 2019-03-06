<?php
/**
 * Class MultipartStreamTest
 *
 * @filesource   MultipartStreamTest.php
 * @created      21.12.2018
 * @package      chillerlan\HTTPTest\Psr7
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr17;
use chillerlan\HTTP\Psr7\MultipartStream;
use GuzzleHttp\Psr7\FnStream;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MultipartStreamTest extends TestCase{

	public function testCreatesDefaultBoundary(){
		$this->assertRegExp('/^[a-f\d]{40}$/', (new MultipartStream)->getBoundary());
	}

	public function testCanProvideBoundary(){
		$this->assertSame('foo', (new MultipartStream([], 'foo'))->getBoundary());
	}

	public function testIsAlwaysReadableNotWritable(){
		$s = new MultipartStream;

		$this->assertTrue($s->isReadable());
		$this->assertFalse($s->isWritable());
	}

	public function testWriteError(){
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Cannot write to a MultipartStream, use MultipartStream::addElement() instead.');

		(new MultipartStream)->write('foo');
	}

	public function testAlreadyBuiltError(){
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Stream already built');

		(new MultipartStream)->build()->addElement([]);
	}

	public function testCanCreateEmptyStream(){
		$stream   = new MultipartStream;
		$boundary = $stream->getBoundary();
		$this->assertSame(strlen($boundary) + 6, $stream->getSize());
		$this->assertSame("--{$boundary}--\r\n", $stream->getContents());
	}

	public function testEnsureContentsElement(){
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('A "contents" element is required');

		new MultipartStream([['foo' => 'bar']]);
	}

	public function testEnsureNameElement(){
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('A "name" element is required');

		new MultipartStream([['contents' => 'bar']]);
	}

	public function testSerializesFields(){
		$stream = new MultipartStream([
			['name' => 'foo', 'contents' => 'bar'],
			['name' => 'baz', 'contents' => 'bam'],
		], 'boundary');

		$this->assertEquals(
			"--boundary\r\nContent-Disposition: form-data; name=\"foo\"\r\nContent-Length: 3\r\n\r\nbar\r\n".
			"--boundary\r\nContent-Disposition: form-data; name=\"baz\"\r\nContent-Length: 3\r\n\r\nbam\r\n".
			"--boundary--\r\n",
			(string)$stream
		);
	}

	public function testSerializesNonStringFields(){
		$stream = new MultipartStream([
			['name' => 'int', 'contents' => 1],
			['name' => 'bool', 'contents' => false],
			['name' => 'bool2', 'contents' => true],
			['name' => 'float', 'contents' => 1.1],
		], 'boundary');

		$this->assertEquals(
			"--boundary\r\nContent-Disposition: form-data; name=\"int\"\r\nContent-Length: 1\r\n\r\n1\r\n".
			"--boundary\r\nContent-Disposition: form-data; name=\"bool\"\r\n\r\n\r\n".
			"--boundary\r\nContent-Disposition: form-data; name=\"bool2\"\r\nContent-Length: 1\r\n\r\n1\r\n".
			"--boundary\r\nContent-Disposition: form-data; name=\"float\"\r\nContent-Length: 3\r\n\r\n1.1\r\n".
			"--boundary--\r\n",
			(string)$stream
		);
	}

	public function testSerializesFiles(){

		$stream = new MultipartStream([
			['name' => 'foo', 'contents' => FnStream::decorate(
				Psr17\create_stream_from_input('foo'), [
				'getMetadata' => function(){
					return '/foo/bar.txt';
				}
			])],
			['name' => 'qux', 'contents' => FnStream::decorate(
				Psr17\create_stream_from_input('baz'), [
				'getMetadata' => function(){
					return '/foo/baz.jpg';
				}
			])],
			['name' => 'qux', 'contents' => FnStream::decorate(
				Psr17\create_stream_from_input('bar'), [
				'getMetadata' => function(){
					return '/foo/bar.gif';
				}
			])],
		], 'boundary');

		$this->assertEquals(
			"--boundary\r\nContent-Disposition: form-data; name=\"foo\"; filename=\"bar.txt\"\r\nContent-Length: 3\r\n".
			"Content-Type: text/plain\r\n\r\nfoo\r\n".
			"--boundary\r\nContent-Disposition: form-data; name=\"qux\"; filename=\"baz.jpg\"\r\nContent-Length: 3\r\n".
			"Content-Type: image/jpeg\r\n\r\nbaz\r\n".
			"--boundary\r\nContent-Disposition: form-data; name=\"qux\"; filename=\"bar.gif\"\r\nContent-Length: 3\r\n".
			"Content-Type: image/gif\r\n\r\nbar\r\n".
			"--boundary--\r\n",
			(string)$stream
		);
	}

	public function testSerializesFilesWithCustomHeaders(){

		$stream = new MultipartStream([[
			'name'     => 'foo',
			'contents' => FnStream::decorate(
				Psr17\create_stream_from_input('foo'), [
				'getMetadata' => function(){
					return '/foo/bar.txt';
				}
			]),
			'headers'  => ['x-foo' => 'bar', 'content-disposition' => 'custom']
		]], 'boundary');

		$this->assertEquals(
			"--boundary\r\nx-foo: bar\r\ncontent-disposition: custom\r\nContent-Length: 3\r\n".
			"Content-Type: text/plain\r\n\r\nfoo\r\n--boundary--\r\n",
			(string)$stream
		);
	}

	public function testSerializesFilesWithCustomHeadersAndMultipleValues(){

		$stream = new MultipartStream([[
			'name'     => 'foo',
			'contents' => FnStream::decorate(
				Psr17\create_stream_from_input('foo'), [
				'getMetadata' => function(){
					return '/foo/bar.txt';
				}
			]),
			'headers'  => ['x-foo' => 'bar', 'content-disposition' => 'custom']
		], [
			'name'     => 'foo',
			'contents' => FnStream::decorate(
				Psr17\create_stream_from_input('baz'), [
				'getMetadata' => function(){
					return '/foo/baz.jpg';
				}
			]),
			'headers'  => ['cOntenT-Type' => 'custom'],
		]], 'boundary');

		$this->assertEquals(
			"--boundary\r\nx-foo: bar\r\ncontent-disposition: custom\r\nContent-Length: 3\r\n".
			"Content-Type: text/plain\r\n\r\nfoo\r\n".
			"--boundary\r\ncOntenT-Type: custom\r\nContent-Disposition: form-data; name=\"foo\"; ".
			"filename=\"baz.jpg\"\r\nContent-Length: 3\r\n\r\nbaz\r\n".
			"--boundary--\r\n",
			(string)$stream
		);
	}

}
