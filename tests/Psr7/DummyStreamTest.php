<?php
/**
 * Class DummyStreamTest
 *
 * see https://github.com/guzzle/psr7/blob/815698d9f11c908bc59471d11f642264b533346a/tests/FnStreamTest.php
 *
 * @created      19.07.2023
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2023 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\DummyStream;
use chillerlan\HTTPTest\FactoryTrait;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class DummyStreamTest extends TestCase{
	use FactoryTrait;

	public function testDefaultStream():void{
		$dummy = new DummyStream;

		$this::assertTrue($dummy->isReadable());
		$this::assertTrue($dummy->isWritable());
		$this::assertTrue($dummy->isSeekable());
		$this::assertSame(4, $dummy->write('data'));
		$this::assertSame('php://temp', $dummy->getMetadata('uri'));
		$this::assertIsArray($dummy->getMetadata());
		$this::assertSame(4, $dummy->getSize());
		$this::assertSame(4, $dummy->tell());
		$this::assertFalse($dummy->eof());
		$dummy->seek(2);
		$this::assertSame('ta', $dummy->read(2));
		$dummy->rewind();
		$this::assertSame('data', $dummy->getContents());
		$this::assertSame('data', (string)$dummy);
		$dummy->close();
	}

	public function testProxiesToFunction():void{
		$dummy = new DummyStream;
		$dummy->dummyOverrideMethod('read', function(int $length):string{
			TestCase::assertSame(3, $length);

			return 'foo';
		});

		$this::assertSame('foo', $dummy->read(3));
	}

	public function testCanCloseOnDestruct():void{
		$called = false;
		$dummy = new DummyStream;

		$dummy->dummyOverrideMethod('close', function() use (&$called):void{
			$called = true;
		});

		unset($dummy);

		$this::assertTrue($called);
	}

	public function testDecoratesWithCustomizations(): void{
		$called = false;

		$a = $this->streamFactory->createStream('foo');

		$b = new DummyStream($a, [
			'read' => function(int $length) use (&$called, $a):string{
				$called = true;

				return $a->read($length);
			},
		]);

		$b->rewind();

		$this::assertSame('foo', $b->read(3));
		$this::assertTrue($called);
	}

}
