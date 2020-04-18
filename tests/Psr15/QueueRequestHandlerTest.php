<?php
/**
 * Class QueueRequestHandlerTest
 *
 * @filesource   QueueRequestHandlerTest.php
 * @created      09.03.2019
 * @package      chillerlan\HTTPTest\Psr15
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr15;

use chillerlan\HTTP\Psr7\Response;
use chillerlan\HTTP\Psr15\{MiddlewareException, QueueRequestHandler};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

use function array_keys;
use function chillerlan\HTTP\Psr17\create_server_request_from_globals;

class QueueRequestHandlerTest extends TestCase{

	public function testHandler(){

		$middlewareStack = [
			new class() implements MiddlewareInterface{
				public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
					TestCase::assertSame([], $request->getAttributes());
					$r = $handler->handle($request->withAttribute('foo1', 'bar1'));
					TestCase::assertTrue($r->hasHeader('X-Out-Second'));

					return $r->withHeader('X-Out-Third', '1');
				}
			},
			new class() implements MiddlewareInterface{
				public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
					TestCase::assertSame(['foo1' => 'bar1'], $request->getAttributes());
					$r = $handler->handle($request->withAttribute('foo2', 'bar2'));
					TestCase::assertTrue($r->hasHeader('X-Out-First'));

					return $r->withHeader('X-Out-Second', '1');
				}
			},
		];

		$middleware3 = new class() implements MiddlewareInterface{
			public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
				TestCase::assertSame(['foo1' => 'bar1', 'foo2' => 'bar2'], $request->getAttributes());
				$r = $handler->handle($request->withAttribute('foo3', 'bar3'));

				return $r->withHeader('X-Out-First', '1');
			}
		};

		// Fallback handler:
		$fallbackHandler = new class() implements RequestHandlerInterface{
			public function handle(ServerRequestInterface $request):ResponseInterface{
				TestCase::assertSame(['foo1' => 'bar1', 'foo2' => 'bar2', 'foo3' => 'bar3'], $request->getAttributes());
				return new Response(200);
			}
		};

		// Create request handler instance:
		$handler = new QueueRequestHandler($middlewareStack, $fallbackHandler);

		// coverage
		$handler->add($middleware3);
		// reverse the stack (reversed behaviour is similar to RecursiveDispatcher)
#		$handler->reverseStack();

		// execute it:
		$response = $handler->handle(create_server_request_from_globals());

		$this::assertSame(
			['X-Out-First', 'X-Out-Second', 'X-Out-Third'],
			array_keys($response->getHeaders())
		);
	}

	public function testInvalidMiddlewareException(){
		$this->expectException(MiddlewareException::class);
		$this->expectExceptionMessage('invalid middleware');

		new QueueRequestHandler(['foo']);
	}

}
