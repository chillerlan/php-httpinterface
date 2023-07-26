<?php
/**
 * Class MessageTest
 *
 * @created      26.07.2023
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2023 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\Message;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

/**
 *
 */
class MessageTest extends TestCase{

	public function testNullBody():void{
		$message = new Message;

		$this::assertInstanceOf(StreamInterface::class, $message->getBody());
		$this::assertSame('', (string)$message->getBody());
	}

	public function testSameInstanceWhenSameBody():void{
		$message = new Message;

		$b = $message->getBody();
		$this::assertSame($message, $message->withBody($b));
	}

	public function testReturnsEmptyHeadersArray():void{
		$message = new Message;

		$this::assertEmpty($message->getHeaders());
	}

	public function testWithHeader():void{
		$message  = (new Message)->withHeader('Foo', 'Bar');
		// this is horseshit btw., the whole "immutability" of PSR-7 is horseshit and y'all know it
		$message2 = $message->withHeader('baZ', 'Bam');

		$this::assertSame(['Foo' => ['Bar']], $message->getHeaders());
		$this::assertSame(['Foo' => ['Bar'], 'baZ' => ['Bam']], $message2->getHeaders());
		$this::assertSame('Bam', $message2->getHeaderLine('baz'));
		$this::assertSame(['Bam'], $message2->getHeader('baz'));
	}

	public function testWithHeaderAsArray():void{
		$message  = (new Message)->withHeader('Foo', 'Bar');
		$message2 = $message->withHeader('baZ', ['Bam', 'Bar']);

		$this::assertSame(['Foo' => ['Bar']], $message->getHeaders());
		$this::assertSame(['Foo' => ['Bar'], 'baZ' => ['Bam', 'Bar']], $message2->getHeaders());
		$this::assertSame('Bam, Bar', $message2->getHeaderLine('baz'));
		$this::assertSame(['Bam', 'Bar'], $message2->getHeader('baz'));
	}

	public function testWithHeaderReplacesDifferentCase():void{
		$message  = (new Message)->withHeader('Foo', 'Bar');
		$message2 = $message->withHeader('foO', 'Bam');

		$this::assertSame(['Foo' => ['Bar']], $message->getHeaders());
		$this::assertSame(['foO' => ['Bam']], $message2->getHeaders());
		$this::assertSame('Bam', $message2->getHeaderLine('foo'));
		$this::assertSame(['Bam'], $message2->getHeader('foo'));
	}

	public function testWithAddedHeader():void{
		$message  = (new Message)->withHeader('Foo', 'Bar');
		$message2 = $message->withAddedHeader('foO', 'Baz');

		$this::assertSame(['Foo' => ['Bar']], $message->getHeaders());
		$this::assertSame(['Foo' => ['Bar', 'Baz']], $message2->getHeaders());
		$this::assertSame('Bar, Baz', $message2->getHeaderLine('foo'));
		$this::assertSame(['Bar', 'Baz'], $message2->getHeader('foo'));
	}

	public function testWithAddedHeaderAsArray():void{
		$message  = (new Message)->withHeader('Foo', 'Bar');
		$message2 = $message->withAddedHeader('foO', ['Baz', 'Bam',]);

		$this::assertSame(['Foo' => ['Bar']], $message->getHeaders());
		$this::assertSame(['Foo' => ['Bar', 'Baz', 'Bam']], $message2->getHeaders());
		$this::assertSame('Bar, Baz, Bam', $message2->getHeaderLine('foo'));
		$this::assertSame(['Bar', 'Baz', 'Bam'], $message2->getHeader('foo'));
	}

	public function testWithAddedHeaderThatDoesNotExist():void{
		$message  = (new Message)->withHeader('Foo', 'Bar');
		$message2 = $message->withAddedHeader('nEw', 'Baz');

		$this::assertSame(['Foo' => ['Bar']], $message->getHeaders());
		$this::assertSame(['Foo' => ['Bar'], 'nEw' => ['Baz']], $message2->getHeaders());
		$this::assertSame('Baz', $message2->getHeaderLine('new'));
		$this::assertSame(['Baz'], $message2->getHeader('new'));
	}

	public function testWithoutHeaderThatExists():void{
		$message  = (new Message)->withHeader('Foo', 'Bar')->withHeader('Baz', 'Bam');
		$message2 = $message->withoutHeader('foO');

		$this::assertTrue($message->hasHeader('foo'));
		$this::assertSame(['Foo' => ['Bar'], 'Baz' => ['Bam']], $message->getHeaders());
		$this::assertFalse($message2->hasHeader('foo'));
		$this::assertSame(['Baz' => ['Bam']], $message2->getHeaders());
	}

	public function testWithoutHeaderThatDoesNotExist():void{
		$message  = (new Message)->withHeader('Baz', 'Bam');
		$message2 = $message->withoutHeader('foO');

		$this::assertSame($message, $message2);
		$this::assertFalse($message2->hasHeader('foo'));
		$this::assertSame(['Baz' => ['Bam']], $message2->getHeaders());
	}

	public function testSameInstanceWhenRemovingMissingHeader():void{
		$message = new Message;
		$this::assertSame($message, $message->withoutHeader('foo'));
	}

	public function testHeaderValuesAreTrimmed():void{
		$message2 = (new Message)->withHeader('Bar', " \t \tFoo\t \t ");
		$message3 = (new Message)->withAddedHeader('Bar', " \t \tFoo\t \t ");

		foreach([$message2, $message3] as $message){
			$this::assertSame(['Bar' => ['Foo']], $message->getHeaders());
			$this::assertSame('Foo', $message->getHeaderLine('Bar'));
			$this::assertSame(['Foo'], $message->getHeader('Bar'));
		}
	}

	public function testSupportNumericHeaderValues():void{
		$r = (new Message)->withHeader('Content-Length', 69);

		$this::assertSame(['Content-Length' => ['69']], $r->getHeaders());
		$this::assertSame('69', $r->getHeaderLine('Content-Length'));
	}

}
