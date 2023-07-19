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
use Psr\Http\Message\ResponseInterface;

class ResponseFactoryTest extends TestCase{
	use FactoryTrait;

	public static function dataCodes():array{
		return [
			'200' => [200],
			'301' => [301],
			'404' => [404],
			'500' => [500],
		];
	}

	#[DataProvider('dataCodes')]
	public function testCreateResponse($code){
		$response = $this->responseFactory->createResponse($code);

		$this::assertInstanceOf(ResponseInterface::class, $response);
		$this::assertSame($code, $response->getStatusCode());
	}

}
