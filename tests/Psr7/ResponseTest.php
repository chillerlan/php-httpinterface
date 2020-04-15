<?php
/**
 * Class ResponseTest
 *
 * @link https://github.com/guzzle/psr7/blob/4b981cdeb8c13d22a6c193554f8c686f53d5c958/tests/ResponseTest.php
 *
 * @filesource   ResponseTest.php
 * @created      12.08.2018
 * @package      chillerlan\HTTPTest\Psr7
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\Response;
use chillerlan\HTTP\Psr17;
use Psr\Http\Message\StreamInterface;
use PHPUnit\Framework\TestCase;

use function chillerlan\HTTP\Psr17\create_stream;

class ResponseTest extends TestCase{

	public function testDefaultConstructor(){
		$r = new Response;

		$this::assertSame(200, $r->getStatusCode());
		$this::assertSame('1.1', $r->getProtocolVersion());
		$this::assertSame('OK', $r->getReasonPhrase());
		$this::assertSame([], $r->getHeaders());
		$this::assertInstanceOf(StreamInterface::class, $r->getBody());
		$this::assertSame('', (string)$r->getBody());
	}

	public function testCanConstructWithStatusCode(){
		$r = new Response(404);

		$this::assertSame(404, $r->getStatusCode());
		$this::assertSame('Not Found', $r->getReasonPhrase());
	}

	public function testConstructorDoesNotReadStreamBody(){
		$body = $this->getMockBuilder(StreamInterface::class)->getMock();
		$body->expects($this->never())->method('__toString');

		$this::assertSame($body, (new Response(200, [], $body))->getBody());
	}

	public function testStatusCanBeNumericString(){
		$r  = new Response('404');
		$r2 = $r->withStatus('201');

		$this::assertSame(404, $r->getStatusCode());
		$this::assertSame('Not Found', $r->getReasonPhrase());
		$this::assertSame(201, $r2->getStatusCode());
		$this::assertSame('Created', $r2->getReasonPhrase());
	}

	public function testCanConstructWithHeaders(){
		$r = new Response(200, ['Foo' => 'Bar']);

		$this::assertSame(['Foo' => ['Bar']], $r->getHeaders());
		$this::assertSame('Bar', $r->getHeaderLine('Foo'));
		$this::assertSame(['Bar'], $r->getHeader('Foo'));
	}

	public function testCanConstructWithHeadersAsArray(){
		$r = new Response(200, ['Foo' => ['baz', 'bar']]);

		$this::assertSame(['Foo' => ['baz, bar']], $r->getHeaders());
		$this::assertSame('baz, bar', $r->getHeaderLine('Foo'));
		$this::assertSame(['baz, bar'], $r->getHeader('Foo'));
	}

	public function testCanConstructWithBody(){
		$r = new Response(200, [], 'baz');

		$this::assertInstanceOf(StreamInterface::class, $r->getBody());
		$this::assertSame('baz', (string)$r->getBody());
	}

	public function testNullBody(){
		$r = new Response(200, [], null);

		$this::assertInstanceOf(StreamInterface::class, $r->getBody());
		$this::assertSame('', (string)$r->getBody());
	}

	public function testFalseyBody(){
		$r = new Response(200, [], '0');

		$this::assertInstanceOf(StreamInterface::class, $r->getBody());
		$this::assertSame('0', (string)$r->getBody());
	}

	public function testCanConstructWithReason(){
		$r = new Response(200, [], null, '1.1', 'bar');
		$this::assertSame('bar', $r->getReasonPhrase());

		$r = new Response(200, [], null, '1.1', '0');
		$this::assertSame('0', $r->getReasonPhrase(), 'Falsey reason works');
	}

	public function testCanConstructWithProtocolVersion(){
		$r = new Response(200, [], null, '1000');
		$this::assertSame('1000', $r->getProtocolVersion());
	}

	public function testWithStatusCodeAndNoReason(){
		$r = (new Response)->withStatus(201);
		$this::assertSame(201, $r->getStatusCode());
		$this::assertSame('Created', $r->getReasonPhrase());
	}

	public function testWithStatusCodeAndReason(){
		$r = (new Response)->withStatus(201, 'Foo');
		$this::assertSame(201, $r->getStatusCode());
		$this::assertSame('Foo', $r->getReasonPhrase());

		$r = (new Response)->withStatus(201, '0');
		$this::assertSame(201, $r->getStatusCode());
		$this::assertSame('0', $r->getReasonPhrase(), 'Falsey reason works');
	}

	public function testWithProtocolVersion(){
		$r = (new Response)->withProtocolVersion('1000');
		$this::assertSame('1000', $r->getProtocolVersion());
	}

	public function testSameInstanceWhenSameProtocol(){
		$r = new Response();
		$this::assertSame($r, $r->withProtocolVersion('1.1'));
	}

	public function testWithBody(){
		$r = (new Response)->withBody(create_stream('0'));
		$this::assertInstanceOf(StreamInterface::class, $r->getBody());
		$this::assertSame('0', (string) $r->getBody());
	}

	public function testSameInstanceWhenSameBody(){
		$r = new Response();

		$b = $r->getBody();
		$this::assertSame($r, $r->withBody($b));
	}

	public function testWithHeader(){
		$r  = new Response(200, ['Foo' => 'Bar']);
		$r2 = $r->withHeader('baZ', 'Bam');

		$this::assertSame(['Foo' => ['Bar']], $r->getHeaders());
		$this::assertSame(['Foo' => ['Bar'], 'baZ' => ['Bam']], $r2->getHeaders());
		$this::assertSame('Bam', $r2->getHeaderLine('baz'));
		$this::assertSame(['Bam'], $r2->getHeader('baz'));
	}

	public function testWithHeaderAsArray(){
		$r  = new Response(200, ['Foo' => 'Bar']);
		$r2 = $r->withHeader('baZ', ['Bam', 'Bar']);

		$this::assertSame(['Foo' => ['Bar']], $r->getHeaders());
		$this::assertSame(['Foo' => ['Bar'], 'baZ' => ['Bam', 'Bar']], $r2->getHeaders());
		$this::assertSame('Bam, Bar', $r2->getHeaderLine('baz'));
		$this::assertSame(['Bam', 'Bar'], $r2->getHeader('baz'));
	}

	public function testWithHeaderReplacesDifferentCase(){
		$r  = new Response(200, ['Foo' => 'Bar']);
		$r2 = $r->withHeader('foO', 'Bam');

		$this::assertSame(['Foo' => ['Bar']], $r->getHeaders());
		$this::assertSame(['foO' => ['Bam']], $r2->getHeaders());
		$this::assertSame('Bam', $r2->getHeaderLine('foo'));
		$this::assertSame(['Bam'], $r2->getHeader('foo'));
	}

	public function testWithAddedHeader(){
		$r  = new Response(200, ['Foo' => 'Bar']);
		$r2 = $r->withAddedHeader('foO', 'Baz');

		$this::assertSame(['Foo' => ['Bar']], $r->getHeaders());
		$this::assertSame(['Foo' => ['Bar', 'Baz']], $r2->getHeaders());
		$this::assertSame('Bar, Baz', $r2->getHeaderLine('foo'));
		$this::assertSame(['Bar', 'Baz'], $r2->getHeader('foo'));
	}

	public function testWithAddedHeaderAsArray(){
		$r  = new Response(200, ['Foo' => 'Bar']);
		$r2 = $r->withAddedHeader('foO', ['Baz', 'Bam',]);

		$this::assertSame(['Foo' => ['Bar']], $r->getHeaders());
		$this::assertSame(['Foo' => ['Bar', 'Baz', 'Bam']], $r2->getHeaders());
		$this::assertSame('Bar, Baz, Bam', $r2->getHeaderLine('foo'));
		$this::assertSame(['Bar', 'Baz', 'Bam'], $r2->getHeader('foo'));
	}

	public function testWithAddedHeaderThatDoesNotExist(){
		$r  = new Response(200, ['Foo' => 'Bar']);
		$r2 = $r->withAddedHeader('nEw', 'Baz');

		$this::assertSame(['Foo' => ['Bar']], $r->getHeaders());
		$this::assertSame(['Foo' => ['Bar'], 'nEw' => ['Baz']], $r2->getHeaders());
		$this::assertSame('Baz', $r2->getHeaderLine('new'));
		$this::assertSame(['Baz'], $r2->getHeader('new'));
	}

	public function testWithoutHeaderThatExists(){
		$r  = new Response(200, ['Foo' => 'Bar', 'Baz' => 'Bam']);
		$r2 = $r->withoutHeader('foO');

		$this::assertTrue($r->hasHeader('foo'));
		$this::assertSame(['Foo' => ['Bar'], 'Baz' => ['Bam']], $r->getHeaders());
		$this::assertFalse($r2->hasHeader('foo'));
		$this::assertSame(['Baz' => ['Bam']], $r2->getHeaders());
	}

	public function testWithoutHeaderThatDoesNotExist(){
		$r  = new Response(200, ['Baz' => 'Bam']);
		$r2 = $r->withoutHeader('foO');

		$this::assertSame($r, $r2);
		$this::assertFalse($r2->hasHeader('foo'));
		$this::assertSame(['Baz' => ['Bam']], $r2->getHeaders());
	}

	public function testSameInstanceWhenRemovingMissingHeader(){
		$r = new Response();
		$this::assertSame($r, $r->withoutHeader('foo'));
	}

	public function testHeaderValuesAreTrimmed(){
		$r1 = new Response(200, ['Bar' => " \t \tFoo\t \t "]);
		$r2 = (new Response)->withHeader('Bar', " \t \tFoo\t \t ");
		$r3 = (new Response)->withAddedHeader('Bar', " \t \tFoo\t \t ");;

		foreach([$r1, $r2, $r3] as $r){
			$this::assertSame(['Bar' => ['Foo']], $r->getHeaders());
			$this::assertSame('Foo', $r->getHeaderLine('Bar'));
			$this::assertSame(['Foo'], $r->getHeader('Bar'));
		}
	}

}
