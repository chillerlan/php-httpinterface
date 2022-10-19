<?php
/**
 * Class FactoryHelpersTest
 *
 * @created      31.01.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr17;

use chillerlan\HTTP\Psr17\FactoryHelpers;
use chillerlan\HTTPTest\TestAbstract;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException, stdClass;

use function fopen, fseek, fwrite, simplexml_load_string;

class FactoryHelpersTest extends TestAbstract{

	public function testCreateStream():void{
		$stream = FactoryHelpers::create_stream('test');

		$this::assertInstanceOf(Streaminterface::class, $stream);
		$this::assertSame('test', $stream->getContents());
	}

	public function testCreateStreamInvalidModeException():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('invalid mode');

		FactoryHelpers::create_stream('test', 'foo');
	}

	public function streamInputProvider():array{

		$fh = fopen('php://temp', 'r+');
		fwrite($fh, 'resourcetest');
		fseek($fh, 0);

		$xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><root><foo>bar</foo></root>');

		return [
			'string'          => ['stringtest', 'stringtest'],
#			'file'            => [__DIR__.'/streaminput.txt', 'filetest'.PHP_EOL],
			'resource'        => [$fh, 'resourcetest'],
			'streaminterface' => [FactoryHelpers::create_stream('streaminterfacetest'), 'streaminterfacetest'],
			'tostring'        => [$xml->foo, 'bar'],
		];
	}

	/**
	 * @dataProvider streamInputProvider
	 *
	 * @param        $input
	 * @param string $content
	 */
	public function testCreateStreamFromInput($input, string $content):void{
		$this::assertSame($content, FactoryHelpers::create_stream_from_input($input)->getContents());
	}

	public function testCreateStreamFromInputException():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid resource type: object');

		FactoryHelpers::create_stream_from_input(new stdClass);
	}

}
