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

declare(strict_types=1);

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Common\FactoryUtils;
use chillerlan\HTTP\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 *
 */
class ResponseTest extends TestCase{

	public function testDefaultConstructor():void{
		$response = new Response;

		$this::assertSame(200, $response->getStatusCode());
		$this::assertSame('1.1', $response->getProtocolVersion());
		$this::assertSame('OK', $response->getReasonPhrase());
		$this::assertSame([], $response->getHeaders());
		$this::assertInstanceOf(StreamInterface::class, $response->getBody());
		$this::assertSame('', (string)$response->getBody());
	}

	public function testCanConstructWithStatusCode():void{
		$response = new Response(404);

		$this::assertSame(404, $response->getStatusCode());
		$this::assertSame('Not Found', $response->getReasonPhrase());
	}

	public function testCanConstructWithReason():void{
		$response = new Response(200, 'bar');
		$this::assertSame('bar', $response->getReasonPhrase());

		$response = new Response(200, '0');
		$this::assertSame('0', $response->getReasonPhrase(), 'Falsey reason works');
	}

	public function testWithStatusCodeAndNoReason():void{
		$response = (new Response)->withStatus(201);
		$this::assertSame(201, $response->getStatusCode());
		$this::assertSame('Created', $response->getReasonPhrase());
	}

	public function testWithStatusCodeAndReason():void{
		$response = (new Response)->withStatus(201, 'Foo');
		$this::assertSame(201, $response->getStatusCode());
		$this::assertSame('Foo', $response->getReasonPhrase());

		$response = (new Response)->withStatus(201, '0');
		$this::assertSame(201, $response->getStatusCode());
		$this::assertSame('0', $response->getReasonPhrase(), 'Falsey reason works');
	}

	public function testWithProtocolVersion():void{
		$response = (new Response)->withProtocolVersion('1000');
		$this::assertSame('1000', $response->getProtocolVersion());
	}

	public function testSameInstanceWhenSameProtocol():void{
		$response = new Response;
		$this::assertSame($response, $response->withProtocolVersion('1.1'));
	}

	public function testWithBody():void{
		$response = (new Response)->withBody(FactoryUtils::createStream('0'));
		$this::assertInstanceOf(StreamInterface::class, $response->getBody());
		$this::assertSame('0', (string) $response->getBody());
	}

}
