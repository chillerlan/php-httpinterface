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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class RequestFactoryTest extends TestCase{
	use FactoryTrait;

	public static function dataMethods():array{
		return [
			'GET'     => ['GET'],
			'POST'    => ['POST'],
			'PUT'     => ['PUT'],
			'DELETE'  => ['DELETE'],
			'OPTIONS' => ['OPTIONS'],
			'HEAD'    => ['HEAD'],
		];
	}

	#[DataProvider('dataMethods')]
	public function testCreateRequest(string $method):void{
		$uri     = 'https://example.com/';
		$request = $this->requestFactory->createRequest($method, $uri);

		$this::assertInstanceOf(RequestInterface::class, $request);
		$this::assertSame($method, $request->getMethod());
		$this::assertSame($uri, (string)$request->getUri());
	}

	public function testCreateRequestWithUri():void{
		$method  = 'GET';
		$uri     = 'https://example.com/';
		$request = $this->requestFactory->createRequest($method, $this->uriFactory->createUri($uri));

		$this::assertSame($method, $request->getMethod());
		$this::assertSame($uri, (string)$request->getUri());
	}

}
