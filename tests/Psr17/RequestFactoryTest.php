<?php
/**
 * @author       http-factory-tests Contributors
 * @license      MIT
 * @link         https://github.com/http-interop/http-factory-tests
 *
 * @noinspection PhpUndefinedConstantInspection
 */

namespace chillerlan\HTTPTest\Psr17;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use function class_exists;
use function defined;

class RequestFactoryTest extends TestCase{

	protected RequestFactoryInterface $requestFactory;
	protected UriFactoryInterface     $uriFactory;

	public function setUp():void{

		if(!defined('REQUEST_FACTORY') || !class_exists(REQUEST_FACTORY)){
			$this::markTestSkipped('REQUEST_FACTORY class name not provided');
		}

		if(!defined('URI_FACTORY') || !class_exists(URI_FACTORY)){
			$this::markTestSkipped('URI_FACTORY class name not provided');
		}

		$this->requestFactory = new (REQUEST_FACTORY);
		$this->uriFactory     = new (URI_FACTORY);
	}

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
