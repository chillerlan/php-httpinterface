<?php
/**
 * Class MultipartStreamBuilderTest
 *
 * @created      19.07.2023
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2023 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Common;

use chillerlan\HTTP\Common\MultipartStreamBuilder;
use chillerlan\HTTPTest\FactoryTrait;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use InvalidArgumentException;

/**
 *
 */
class MultipartStreamBuilderTest extends TestCase{
	use FactoryTrait;

	protected MultipartStreamBuilder $multipartStreamBuilder;

	protected function __setUp():void{
		$this->multipartStreamBuilder = new MultipartStreamBuilder();
	}

	public function testCreatesDefaultBoundary():void{
		$this::assertMatchesRegularExpression('/^[a-f\d]{40}$/', $this->multipartStreamBuilder->getBoundary());
	}

	public function testSetBoundary():void{
		$boundary = "0-9a-zA-Z'()+_,-./:=?";
		$this->multipartStreamBuilder->setBoundary($boundary);

		$this::assertSame($boundary, $this->multipartStreamBuilder->getBoundary());
	}

	public function testSetBoundaryEmptyException():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The given boundary is empty');

		$this->multipartStreamBuilder->setBoundary('');
	}

	public function testSetBoundaryInvalidCharException():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The given boundary contains illegal characters');

		$this->multipartStreamBuilder->setBoundary('foo#');
	}

	public function testReset():void{

		$this->multipartStreamBuilder
			->setBoundary('boundary')
			->addString('content a', 'a')
		;

		$this::assertSame(
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"a\"\r\n".
			"Content-Length: 9\r\n".
			"Content-Type: text/plain\r\n".
			"\r\n".
			"content a\r\n".
			"--boundary--\r\n",
			(string)$this->multipartStreamBuilder
		);

		$this->multipartStreamBuilder->reset();

		$boundary = $this->multipartStreamBuilder->getBoundary();

		$this::assertSame("--$boundary--\r\n", $this->multipartStreamBuilder->build()->getContents());
	}

	public function testCanCreateEmptyBody():void{
		$this::assertMatchesRegularExpression("/--[a-f\d]{40}--\r\n/", $this->multipartStreamBuilder->build()->getContents());
	}

	public function testAddFields():void{

		$this->multipartStreamBuilder
			->setBoundary('boundary')
			->addString('content a', 'a')
			->addString('content b', 'b')
		;

		$this::assertSame(
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"a\"\r\n".
			"Content-Length: 9\r\n".
			"Content-Type: text/plain\r\n".
			"\r\n".
			"content a\r\n".
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"b\"\r\n".
			"Content-Length: 9\r\n".
			"Content-Type: text/plain\r\n".
			"\r\n".
			"content b\r\n".
			"--boundary--\r\n",
			(string)$this->multipartStreamBuilder
		);
	}

	public function testAddStreams():void{

		$this->multipartStreamBuilder
			->setBoundary('boundary')
			->addStream($this->streamFactory->createStream('filestream a'), 'a', '/dir/a.txt')
			->addStream($this->streamFactory->createStream('filestream b'), 'b', '/foo/b.jpg')
		;

		$this::assertSame(
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"a\"; filename=\"a.txt\"\r\n".
			"Content-Length: 12\r\n".
			"Content-Type: text/plain\r\n".
			"\r\n".
			"filestream a\r\n".
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"b\"; filename=\"b.jpg\"\r\n".
			"Content-Length: 12\r\n".
			"Content-Type: image/jpeg\r\n".
			"\r\n".
			"filestream b\r\n".
			"--boundary--\r\n",
			(string)$this->multipartStreamBuilder
		);
	}

	public function testAddFieldWithSameName():void{

		$this->multipartStreamBuilder
			->setBoundary('boundary')
			->addString('aaa', 'samename', 'a.txt')
			->addString('bbb', 'samename', 'b.jpg')
		;

		$this::assertSame(
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"samename\"; filename=\"a.txt\"\r\n".
			"Content-Length: 3\r\n".
			"Content-Type: text/plain\r\n".
			"\r\n".
			"aaa\r\n".
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"samename\"; filename=\"b.jpg\"\r\n".
			"Content-Length: 3\r\n".
			"Content-Type: image/jpeg\r\n".
			"\r\n".
			"bbb\r\n".
			"--boundary--\r\n",
			(string)$this->multipartStreamBuilder
		);
	}

	public function testGivenFieldnameCannotBeEmptyException():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid form field name');

		$this->multipartStreamBuilder->addString('content', '');
	}

	public function testCustomHeaders():void{

		$this->multipartStreamBuilder
			->setBoundary('boundary')
			->addStream($this->streamFactory->createStream('filestream a'), 'a', '/dir/a.txt', [
				'x-foo'               => 'bar',
				'content-disposition' => 'custom',
			])
		;

		$this::assertSame(
			"--boundary\r\n".
			"Content-Disposition: custom\r\n".
			"Content-Length: 12\r\n".
			"Content-Type: text/plain\r\n".
			"X-Foo: bar\r\n".
			"\r\n".
			"filestream a\r\n".
			"--boundary--\r\n",
			(string)$this->multipartStreamBuilder
		);
	}

	public function testCustomHeadersAndMultipleValues():void{

		$this->multipartStreamBuilder
			->setBoundary('boundary')
			->addStream($this->streamFactory->createStream('filestream a'), 'a', '/dir/a.txt', [
				'x-foo'               => 'bar',
				'content-disposition' => 'custom',
			])
			->addStream($this->streamFactory->createStream('filestream b'), 'b', '/dir/b.jpg', [
				'cOntenT-Type' => 'custom',
			])
		;

		$this::assertSame(
			"--boundary\r\n".
			"Content-Disposition: custom\r\n".
			"Content-Length: 12\r\n".
			"Content-Type: text/plain\r\n".
			"X-Foo: bar\r\n".
			"\r\n".
			"filestream a\r\n".
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"b\"; filename=\"b.jpg\"\r\n".
			"Content-Length: 12\r\n".
			"Content-Type: custom\r\n".
			"\r\n".
			"filestream b\r\n".
			"--boundary--\r\n",
			(string)$this->multipartStreamBuilder
		);
	}

	public function testSuppressContentTypeHeader():void{

		$this->multipartStreamBuilder
			->setBoundary('boundary')
			->addString(content: 'content a', fieldname: 'a', headers: ['Content-Type' => ''])
		;

		$this::assertSame(
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"a\"\r\n".
			"Content-Length: 9\r\n\r\nc".
			"ontent a\r\n".
			"--boundary--\r\n",
			(string)$this->multipartStreamBuilder
		);

	}

	public function testIgnoresNonContentNonCustomHeaders():void{

		$this->multipartStreamBuilder
			->setBoundary('boundary')
			->addString(content: 'content a', fieldname: 'a', headers: [
				'content-whatever' => 'yay',
				'nope'             => 'nah',
				'x-what'           => 'omg',
				'this'             => 'absolutely not',
			]);

		$this::assertSame(
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"a\"\r\n".
			"Content-Length: 9\r\n".
			"Content-Type: text/plain\r\n".
			"Content-Whatever: yay\r\n".
			"X-What: omg\r\n".
			"\r\n".
			"content a\r\n".
			"--boundary--\r\n",
			(string)$this->multipartStreamBuilder
		);

	}

	public function testNesting():void{

		$mp1 = (clone $this->multipartStreamBuilder)
			->setBoundary('boundary-a')
			->addString('content a1', 'a1', 'a1.txt')
			->addString('content a2', 'a2', 'a2.jpg')
		;

		$mp2 = (clone $this->multipartStreamBuilder)
			->setBoundary('boundary-b')
			->addString('content b1', 'b1', 'b1.txt')
			->addStream(stream: $mp1->build(), headers: ['Content-Type' => 'multipart/form-data; boundary="boundary-a"'])
			->addString('content b2', 'b2', 'b2.jpg')
		;

		$this::assertSame(
			"--boundary-b\r\n".
			"Content-Disposition: form-data; name=\"b1\"; filename=\"b1.txt\"\r\n".
			"Content-Length: 10\r\n".
			"Content-Type: text/plain\r\n".
			"\r\n".
			"content b1\r\n".
			"--boundary-b\r\n".
			"Content-Length: 288\r\n".
			"Content-Type: multipart/form-data; boundary=\"boundary-a\"\r\n".
			"\r\n".
			"--boundary-a\r\n".
			"Content-Disposition: form-data; name=\"a1\"; filename=\"a1.txt\"\r\n".
			"Content-Length: 10\r\n".
			"Content-Type: text/plain\r\n".
			"\r\n".
			"content a1\r\n".
			"--boundary-a\r\n".
			"Content-Disposition: form-data; name=\"a2\"; filename=\"a2.jpg\"\r\n".
			"Content-Length: 10\r\n".
			"Content-Type: image/jpeg\r\n".
			"\r\n".
			"content a2\r\n".
			"--boundary-a--\r\n".
			"\r\n". // does this extra newline bother anyone or can we just ignore it??
			"--boundary-b\r\n".
			"Content-Disposition: form-data; name=\"b2\"; filename=\"b2.jpg\"\r\n".
			"Content-Length: 10\r\n".
			"Content-Type: image/jpeg\r\n".
			"\r\n".
			"content b2\r\n".
			"--boundary-b--\r\n",
			(string)$mp2
		);

	}

	public function testBuildWithMessageInterface():void{

		$request = $this->multipartStreamBuilder
			->setBoundary('boundary')
			->addStream($this->streamFactory->createStream('filestream a'), 'a', '/foo/a.jpg')
			->build($this->requestFactory->createRequest('POST', 'http://example.com/api/media'))
		;

		$this::assertInstanceOf(MessageInterface::class, $request);
		$this::assertTrue($request->hasHeader('content-type'));
		$this::assertSame('multipart/form-data; boundary="boundary"', $request->getHeaderLine('content-type'));

		$this::assertSame(
			"--boundary\r\n".
			"Content-Disposition: form-data; name=\"a\"; filename=\"a.jpg\"\r\n".
			"Content-Length: 12\r\n".
			"Content-Type: image/jpeg\r\n".
			"\r\n".
			"filestream a\r\n".
			"--boundary--\r\n",
			(string)$request->getBody()
		);

	}

	public function testOverwritesContentTypeHeaderInMessage():void{

		$originalRequest = $this->requestFactory
			->createRequest('POST', 'http://example.com/api/media')
			->withHeader('Content-Type', 'whatever')
		;

		$this::assertTrue($originalRequest->hasHeader('content-type'));
		$this::assertSame('whatever', $originalRequest->getHeaderLine('content-type'));


		$modifiedRequest = $this->multipartStreamBuilder
			->setBoundary('boundary')
			->addStream($this->streamFactory->createStream('filestream a'), 'a', '/foo/a.jpg')
			->build($originalRequest)
		;

		$this::assertTrue($modifiedRequest->hasHeader('content-type'));
		$this::assertSame('multipart/form-data; boundary="boundary"', $modifiedRequest->getHeaderLine('content-type'));
	}

}
