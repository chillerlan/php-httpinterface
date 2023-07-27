<?php
/**
 * Class FactoryHelpersTest
 *
 * @created      31.01.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Common;

use chillerlan\HTTP\Common\FactoryHelpers;
use chillerlan\HTTPTest\FactoryTrait;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use stdClass;
use function fopen;
use function fseek;
use function fwrite;
use function simplexml_load_string;

class FactoryHelpersTest extends TestCase{
	use FactoryTrait;

	public function testCreateStream():void{
		$stream = FactoryHelpers::createStream('test');

		$this::assertInstanceOf(Streaminterface::class, $stream);
		$this::assertSame('test', $stream->getContents());
	}

	public function testCreateStreamInvalidModeException():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('invalid mode for writing');

		FactoryHelpers::createStream('test', 'r');
	}

	public static function streamInputProvider():array{
		$fh = fopen('php://temp', 'r+');

		fwrite($fh, 'resourcetest');
		fseek($fh, 0);

		$xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><root><foo>bar</foo></root>');

		return [
			'string'          => ['stringtest', 'stringtest'],
			'resource'        => [$fh, 'resourcetest'],
			'streaminterface' => [FactoryHelpers::createStream('streaminterfacetest'), 'streaminterfacetest'],
			'tostring'        => [$xml->foo, 'bar'],
		];
	}

	#[DataProvider('streamInputProvider')]
	public function testCreateStreamFromInput(mixed $input, string $content):void{
		$this::assertSame($content, FactoryHelpers::createStreamFromSource($input)->getContents());
	}

	public function testCreateStreamFromInputException():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid resource type: object');

		FactoryHelpers::createStreamFromSource(new stdClass);
	}

}
