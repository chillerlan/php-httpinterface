<?php
/**
 * Class PriorityQueueRequestHandlerTest
 *
 * @created      13.03.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr15;

use chillerlan\HTTP\Psr15\{MiddlewareException, PriorityMiddleware, PriorityMiddlewareInterface, PriorityQueueDispatcher};
use chillerlan\HTTPTest\FactoryTrait;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use function array_keys;

/**
 *
 */
class PriorityQueueRequestHandlerTest extends TestCase{
	use FactoryTrait;

	public function testHandler():void{

		$middlewareStack = [
			$this->getNonPriorityMiddleware(0),
			$this->getPriorityMiddleware(2),
			$this->getPriorityMiddleware(3),
			$this->getPriorityMiddleware(1),
		];

		// Create request handler instance:
		$handler = new PriorityQueueDispatcher($middlewareStack);

		// coverage
		$handler->add($this->getNonPriorityMiddleware(1));

		// execute it:
		$response = $handler->handle($this->server->createServerRequestFromGlobals());

		// highest priority shall be processed first and go out last
		$this::assertSame(
			[
				'X-Priority-3',
				'X-Priority-2',
				'X-Priority-1',
				'X-Priority-None-0',
				'X-Priority-None-1',
			],
			array_keys($response->getHeaders())
		);
	}

	public function testNestedHandler():void{

		$middlewareStack = [
			new PriorityMiddleware(
				new PriorityQueueDispatcher([
					$this->getPriorityMiddleware(22),
					$this->getNonPriorityMiddleware(0),
					$this->getPriorityMiddleware(33),
					$this->getPriorityMiddleware(11),
				]),
				2
			),
			$this->getPriorityMiddleware(4),
			$this->getPriorityMiddleware(1),
			$this->getNonPriorityMiddleware(1),
			$this->getPriorityMiddleware(3),
		];

		$handler  = new PriorityQueueDispatcher($middlewareStack);
		$response = $handler->handle($this->server->createServerRequestFromGlobals());

		$this::assertSame(
			[
				'X-Priority-4',
				'X-Priority-3',
				'X-Priority-33',
				'X-Priority-22',
				'X-Priority-11',
				'X-Priority-None-0',
				'X-Priority-1',
				'X-Priority-None-1',
			],
			array_keys($response->getHeaders())
		);
	}

	protected function getNonPriorityMiddleware(int $id):MiddlewareInterface{
		return new class ($id) implements MiddlewareInterface{

			protected int $id;

			public function __construct(int $id){
				$this->id = $id;
			}

			public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
				return $handler->handle($request)->withHeader('X-Priority-None-'.$this->id, '0');
			}
		};
	}

	protected function getPriorityMiddleware(int $priority):PriorityMiddlewareInterface{

		$middleware = new class ($priority) implements MiddlewareInterface{

			protected int $priority;

			public function __construct(int $priority){
				$this->priority = $priority;
			}

			public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
				return $handler->handle($request)->withHeader('X-Priority-'.$this->priority, (string)$this->priority);
			}
		};

		return new PriorityMiddleware($middleware, $priority);
	}

	public function testInvalidMiddlewareException():void{
		$this->expectException(MiddlewareException::class);
		$this->expectExceptionMessage('invalid middleware');

		new PriorityQueueDispatcher(['foo']);
	}

}
