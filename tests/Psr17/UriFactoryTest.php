<?php
/**
 * @author       http-factory-tests Contributors
 * @license      MIT
 * @link         https://github.com/http-interop/http-factory-tests
 *
 * @noinspection PhpUndefinedConstantInspection
 */

namespace chillerlan\HTTPTest\Psr17;

use chillerlan\HTTPTest\FactoryTrait;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

class UriFactoryTest extends TestCase{
	use FactoryTrait;

	public function testCreateUri():void{
		$uriString = 'https://example.com/';

		$uri = $this->uriFactory->createUri($uriString);

		$this::assertInstanceOf(UriInterface::class, $uri);
		$this::assertSame($uriString, (string)$uri);
	}

	public function testExceptionWhenUriIsInvalid():void{
		$this->expectException(InvalidArgumentException::class);
		$this->uriFactory->createUri(':');
	}

}
