<?php
/**
 * @author       http-factory-tests Contributors
 * @license      MIT
 * @link         https://github.com/http-interop/http-factory-tests
 *
 * @noinspection PhpUndefinedConstantInspection
 */

namespace chillerlan\HTTPTest\Psr17;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use function class_exists;
use function defined;

class UriFactoryTest extends TestCase{

	protected UriFactoryInterface $uriFactory;

	public function setUp():void{

		if(!defined('URI_FACTORY') || !class_exists(URI_FACTORY)){
			$this::markTestSkipped('URI_FACTORY class name not provided');
		}

		$this->uriFactory = new (URI_FACTORY);
	}

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
