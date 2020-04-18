<?php
/**
 * Class RecursiveDispatcherTest
 *
 * @filesource   RecursiveDispatcherTest.php
 * @created      15.04.2020
 * @package      chillerlan\HTTPTest\Psr15
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2020 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr15;

use chillerlan\HTTP\Psr15\RecursiveDispatcher;
use chillerlan\HTTP\Psr15\MiddlewareException;
use chillerlan\HTTP\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

use function array_keys;
use function chillerlan\HTTP\Psr17\create_server_request_from_globals;

class RecursiveDispatcherTest extends TestCase{

	public function testHandler(){

		$middlewareStack = [
			new class() implements MiddlewareInterface{
				public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
					TestCase::assertSame(['foo3' => 'bar3', 'foo2' => 'bar2'], $request->getAttributes());
					$r = $handler->handle($request->withAttribute('foo1', 'bar1'));
					TestCase::assertSame([], array_keys($r->getHeaders()));

					return $r->withHeader('X-Out-First', '1');
				}
			},
			new class() implements MiddlewareInterface{
				public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
					TestCase::assertSame(['foo3' => 'bar3'], $request->getAttributes());
					$r = $handler->handle($request->withAttribute('foo2', 'bar2'));
					TestCase::assertSame(['X-Out-First'], array_keys($r->getHeaders()));

					return $r->withHeader('X-Out-Second', '1');
				}
			},
		];

		$middleware3 = new class() implements MiddlewareInterface{
			public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
				TestCase::assertSame([], $request->getAttributes());
				$r = $handler->handle($request->withAttribute('foo3', 'bar3'));
				TestCase::assertSame(['X-Out-First', 'X-Out-Second'], array_keys($r->getHeaders()));

				return $r->withHeader('X-Out-Third', '1');
			}
		};

		// Create request handler instance:
		$handler = new class() implements RequestHandlerInterface{
			public function handle(ServerRequestInterface $request):ResponseInterface{
				TestCase::assertSame(['foo3' => 'bar3', 'foo2' => 'bar2', 'foo1' => 'bar1'], $request->getAttributes());
				return new Response(200);
			}
		};

		$dispatcher = new RecursiveDispatcher($handler);

		$dispatcher->addStack($middlewareStack);

		// coverage
		$dispatcher->add($middleware3);

		$response = $dispatcher->handle(create_server_request_from_globals());

		$this::assertSame(
			['X-Out-First', 'X-Out-Second', 'X-Out-Third'],
			array_keys($response->getHeaders())
		);
	}

	public function testInvalidMiddlewareException(){
		$this->expectException(MiddlewareException::class);
		$this->expectExceptionMessage('invalid middleware');

		$handler = new class() implements RequestHandlerInterface{
			public function handle(ServerRequestInterface $request):ResponseInterface{
				return new Response(200);
			}
		};

		$dispatcher = new RecursiveDispatcher($handler);

		$dispatcher->addStack(['foo']);
	}

}
