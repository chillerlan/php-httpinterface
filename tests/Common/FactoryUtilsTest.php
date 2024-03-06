<?php
/**
 * Class FactoryUtilsTest
 *
 * @created      31.01.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTPTest\Common;

use chillerlan\HTTP\Common\FactoryUtils;
use chillerlan\HTTPTest\FactoryTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException, stdClass;
use function fopen, fseek, fwrite, simplexml_load_string;

/**
 *
 */
class FactoryUtilsTest extends TestCase{
	use FactoryTrait;

	protected function setUp():void{
		$this->initFactories();
	}

	public function testCreateStream():void{
		$stream = FactoryUtils::createStream('test');

		$this::assertInstanceOf(Streaminterface::class, $stream);
		$this::assertSame('test', $stream->getContents());
	}

	public function testCreateStreamInvalidModeException():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('invalid mode for writing');

		FactoryUtils::createStream('test', 'r');
	}

	public static function streamInputProvider():array{
		$fh = fopen('php://temp', 'r+');

		fwrite($fh, 'resourcetest');
		fseek($fh, 0);

		$xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><root><foo>bar</foo></root>');

		return [
			'string'          => ['stringtest', 'stringtest'],
			'resource'        => [$fh, 'resourcetest'],
			'streaminterface' => [FactoryUtils::createStream('streaminterfacetest'), 'streaminterfacetest'],
			'tostring'        => [$xml->foo, 'bar'],
		];
	}

	#[DataProvider('streamInputProvider')]
	public function testCreateStreamFromInput(mixed $input, string $content):void{
		$this::assertSame($content, FactoryUtils::createStreamFromSource($input)->getContents());
	}

	public function testCreateStreamFromInputException():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid resource type: object');

		FactoryUtils::createStreamFromSource(new stdClass);
	}

}
