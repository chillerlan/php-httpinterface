<?php
/**
 * Class ResponseTest
 *
 * @link https://github.com/guzzle/psr7/blob/4b981cdeb8c13d22a6c193554f8c686f53d5c958/tests/ResponseTest.php
 *
 * @created      12.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Common\FactoryHelpers;
use chillerlan\HTTP\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class ResponseTest extends TestCase{

	public function testDefaultConstructor():void{
		$r = new Response;

		$this::assertSame(200, $r->getStatusCode());
		$this::assertSame('1.1', $r->getProtocolVersion());
		$this::assertSame('OK', $r->getReasonPhrase());
		$this::assertSame([], $r->getHeaders());
		$this::assertInstanceOf(StreamInterface::class, $r->getBody());
		$this::assertSame('', (string)$r->getBody());
	}

	public function testCanConstructWithStatusCode():void{
		$r = new Response(404);

		$this::assertSame(404, $r->getStatusCode());
		$this::assertSame('Not Found', $r->getReasonPhrase());
	}

	public function testStatusCanBeNumericString():void{
		$r  = new Response('404');
		$r2 = $r->withStatus('201');

		$this::assertSame(404, $r->getStatusCode());
		$this::assertSame('Not Found', $r->getReasonPhrase());
		$this::assertSame(201, $r2->getStatusCode());
		$this::assertSame('Created', $r2->getReasonPhrase());
	}

	public function testCanConstructWithReason():void{
		$r = new Response(200, 'bar');
		$this::assertSame('bar', $r->getReasonPhrase());

		$r = new Response(200, '0');
		$this::assertSame('0', $r->getReasonPhrase(), 'Falsey reason works');
	}

	public function testWithStatusCodeAndNoReason():void{
		$r = (new Response)->withStatus(201);
		$this::assertSame(201, $r->getStatusCode());
		$this::assertSame('Created', $r->getReasonPhrase());
	}

	public function testWithStatusCodeAndReason():void{
		$r = (new Response)->withStatus(201, 'Foo');
		$this::assertSame(201, $r->getStatusCode());
		$this::assertSame('Foo', $r->getReasonPhrase());

		$r = (new Response)->withStatus(201, '0');
		$this::assertSame(201, $r->getStatusCode());
		$this::assertSame('0', $r->getReasonPhrase(), 'Falsey reason works');
	}

	public function testWithProtocolVersion():void{
		$r = (new Response)->withProtocolVersion('1000');
		$this::assertSame('1000', $r->getProtocolVersion());
	}

	public function testSameInstanceWhenSameProtocol():void{
		$r = new Response;
		$this::assertSame($r, $r->withProtocolVersion('1.1'));
	}

	public function testWithBody():void{
		$r = (new Response)->withBody(FactoryHelpers::createStream('0'));
		$this::assertInstanceOf(StreamInterface::class, $r->getBody());
		$this::assertSame('0', (string) $r->getBody());
	}

}
