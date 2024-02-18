<?php
/**
 * Class RequestTest
 *
 * @link https://github.com/guzzle/psr7/blob/4b981cdeb8c13d22a6c193554f8c686f53d5c958/tests/RequestTest.php
 *
 * @created      12.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\{Request, Uri};
use Fig\Http\Message\RequestMethodInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class RequestTest extends TestCase{

	public function testRequestUriMayBeString():void{
		$this::assertSame('/', (string)(new Request(RequestMethodInterface::METHOD_GET, '/'))->getUri());
	}

	public function testRequestUriMayBeUri():void{
		$uri = new Uri('/');

		$this::assertSame($uri, (new Request('GET', $uri))->getUri());
	}

	public function testValidateRequestUri():void{
		$this->expectException(InvalidArgumentException::class);

		new Request('GET', '///');
	}

	public function testCapitalizesMethod():void{
		$this::assertSame('GET', (new Request('get', '/'))->getMethod());
	}

	public function testCapitalizesWithMethod():void{
		$this::assertSame('PUT', (new Request('GET', '/'))->withMethod('put')->getMethod());
	}

	public function testWithUri():void{
		$request = new Request('GET', '/');
		$uri1    = $request->getUri();
		$uri2    = new Uri('https://www.example.com');

		$this::assertSame($uri1, $request->getUri());

		$request->withUri($uri2);

		$this::assertSame($uri2, $request->getUri());
	}

	public function testSameInstanceWhenSameUri():void{
		$request = new Request('GET', 'https://foo.com');
		$request->withUri($request->getUri());

		$this::assertSame($request, $request);
	}

	public function testWithRequestTarget():void{
		$request = new Request('GET', '/');

		$this::assertSame('/', $request->getRequestTarget());

		$request->withRequestTarget('*');

		$this::assertSame('*', $request->getRequestTarget());
	}

	public function testRequestTargetDoesNotAllowSpaces():void{
		$this->expectException(InvalidArgumentException::class);

		(new Request('GET', '/'))->withRequestTarget('/foo bar');
	}

	public function testRequestTargetDefaultsToSlash():void{
		$request = new Request('GET', '');
		$this::assertSame('/', $request->getRequestTarget());

		$request = new Request('GET', '*');
		$this::assertSame('*', $request->getRequestTarget());

		$request = new Request('GET', 'https://foo.com/bar baz/');
		$this::assertSame('/bar%20baz/', $request->getRequestTarget());
	}

	public function testBuildsRequestTarget():void{
		$this::assertSame('/baz?bar=bam', (new Request('GET', 'https://foo.com/baz?bar=bam'))->getRequestTarget());
	}

	public function testBuildsRequestTargetWithFalseyQuery():void{
		$this::assertSame('/baz?0', (new Request('GET', 'https://foo.com/baz?0'))->getRequestTarget());
	}

	public function testCanGetHeaderAsCsv():void{
		$request = (new Request('GET', 'https://foo.com/baz?bar=bam'))->withHeader('Foo', ['a', 'b', 'c']);

		$this::assertSame('a, b, c', $request->getHeaderLine('Foo'));
		$this::assertSame('', $request->getHeaderLine('Bar'));
	}

	public function testOverridesHostWithUri():void{
		$request = new Request('GET', 'https://foo.com/baz?bar=bam');
		$this::assertSame(['Host' => ['foo.com']], $request->getHeaders());

		$request->withUri(new Uri('https://www.baz.com/bar'));
		$this::assertSame('www.baz.com', $request->getHeaderLine('Host'));
	}

	public function testAddsPortToHeader():void{
		$this::assertSame('foo.com:8124', (new Request('GET', 'https://foo.com:8124/bar'))->getHeaderLine('host'));
	}

	public function testAddsPortToHeaderAndReplacePreviousPort():void{
		$request = (new Request('GET', 'https://foo.com:8124/bar'))
			->withUri(new Uri('https://foo.com:8125/bar'));

		$this::assertSame('foo.com:8125', $request->getHeaderLine('host'));
	}

	public function testWithMethodEmptyMethod():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('HTTP method must not be empty');

		(new Request('GET', '/foo'))->withMethod('');
	}

}
