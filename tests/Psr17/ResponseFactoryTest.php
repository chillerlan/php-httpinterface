<?php
/**
 * @author       http-factory-tests Contributors
 * @license      MIT
 * @link         https://github.com/http-interop/http-factory-tests
 *
 * @noinspection PhpUndefinedConstantInspection
 */

namespace chillerlan\HTTPTest\Psr17;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use function class_exists;
use function defined;

class ResponseFactoryTest extends TestCase{

	protected ResponseFactoryInterface $responseFactory;

	public function setUp():void{

		if(!defined('RESPONSE_FACTORY') || !class_exists(RESPONSE_FACTORY)){
			$this::markTestSkipped('RESPONSE_FACTORY class name not provided');
		}

		$this->responseFactory = new (RESPONSE_FACTORY);
	}

	public static function dataCodes():array{
		return [
			'200' => [200],
			'301' => [301],
			'404' => [404],
			'500' => [500],
		];
	}

	/**
	 * @dataProvider dataCodes
	 */
	public function testCreateResponse($code){
		$response = $this->responseFactory->createResponse($code);

		$this::assertInstanceOf(ResponseInterface::class, $response);
		$this::assertSame($code, $response->getStatusCode());
	}

}
