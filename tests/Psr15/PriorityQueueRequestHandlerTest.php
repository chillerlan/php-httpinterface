<?php
/**
 * Class PriorityQueueRequestHandlerTest
 *
 * @filesource   PriorityQueueRequestHandlerTest.php
 * @created      13.03.2019
 * @package      chillerlan\HTTPTest\Psr15
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr15;

use chillerlan\HTTP\Psr17;
use chillerlan\HTTP\Psr15\{EmptyResponseHandler, PriorityQueueRequestHandler};
use chillerlan\HTTP\Psr15\Middleware\{MiddlewareException, PriorityMiddleware};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class PriorityQueueRequestHandlerTest extends TestCase{

	public function testHandler(){

		$middlewareStack = [
			// coverage
			new class() implements MiddlewareInterface{
				public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
					return $handler->handle($request)->withHeader('X-Priority-none0', '0');
				}
			},
			$this->getPriorityMiddleware(2),
			$this->getPriorityMiddleware(3),
			$this->getPriorityMiddleware(1),
		];

		// Create request handler instance:
		$handler = new PriorityQueueRequestHandler($middlewareStack);

		// coverage
		$handler->add(new class() implements MiddlewareInterface{
			public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
				return $handler->handle($request)->withHeader('X-Priority-none1', '0');
			}
		});

		// execute it:
		$response = $handler->handle(Psr17\create_server_request_from_globals());

		// highest priority shall go out first
		$this->assertSame(
			['X-Priority-3', 'X-Priority-2', 'X-Priority-1', 'X-Priority-none1', 'X-Priority-none0'],
			array_keys($response->getHeaders())
		);
	}

	public function testNestedHandler(){

		$middlewareStack = [
			new PriorityMiddleware(
				new PriorityQueueRequestHandler([
					$this->getPriorityMiddleware(22),
					$this->getPriorityMiddleware(33),
					$this->getPriorityMiddleware(11),
				]),
				2
			),
			$this->getPriorityMiddleware(4),
			$this->getPriorityMiddleware(1),
			$this->getPriorityMiddleware(3),
		];

		$handler  = new PriorityQueueRequestHandler($middlewareStack);
		$response = $handler->handle(Psr17\create_server_request_from_globals());

		$this->assertSame(
			['X-Priority-4', 'X-Priority-3', 'X-Priority-33', 'X-Priority-22', 'X-Priority-11', 'X-Priority-1'],
			array_keys($response->getHeaders())
		);
	}

	protected function getPriorityMiddleware(int $priority):MiddlewareInterface{

		$middleware = new class($priority) implements MiddlewareInterface{

			protected $priority;

			public function __construct(int $priority){
				$this->priority = $priority;
			}

			public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
				return $handler->handle($request)->withHeader('X-Priority-'.$this->priority, (string)$this->priority);
			}
		};

		return new PriorityMiddleware($middleware, $priority);
	}

	public function testInvalidMiddlewareException(){
		$this->expectException(MiddlewareException::class);
		$this->expectExceptionMessage('invalid middleware');

		new PriorityQueueRequestHandler(['foo']);
	}

}
