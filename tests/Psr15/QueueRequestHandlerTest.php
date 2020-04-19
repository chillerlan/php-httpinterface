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
use chillerlan\HTTP\Psr15\{MiddlewareException, QueueDispatcher};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

use function array_keys;
use function chillerlan\HTTP\Psr17\create_server_request_from_globals;

class QueueRequestHandlerTest extends TestCase{

	protected RequestHandlerInterface $dispatcher;

	protected function setUp():void{
		$this->dispatcher = $this->getDispatcher();
 	}

 	protected function getDispatcher():RequestHandlerInterface{
	    // Create request handler instance:
	    $dispatcher = new QueueDispatcher($this->getTestMiddlewareStack(), $this->getTestFallbackHandler());

	    // coverage
	    $dispatcher->add($this->getTestMiddleware());

	    return $dispatcher;
	}

	public function testDispatcher():void{

		// execute it:
		$response = $this->dispatcher->handle(create_server_request_from_globals());

		$this::assertSame(
			['X-Out-First', 'X-Out-Second', 'X-Out-Third'],
			array_keys($response->getHeaders())
		);
	}

	protected function getTestMiddlewareStack():array{
		return [
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
	}

	protected function getTestMiddleware():MiddlewareInterface{
		return new class() implements MiddlewareInterface{
			public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
				TestCase::assertSame([], $request->getAttributes());
				$r = $handler->handle($request->withAttribute('foo3', 'bar3'));
				TestCase::assertSame(['X-Out-First', 'X-Out-Second'], array_keys($r->getHeaders()));

				return $r->withHeader('X-Out-Third', '1');
			}
		};
	}

	protected function getTestFallbackHandler():RequestHandlerInterface{
		return new class() implements RequestHandlerInterface{
			public function handle(ServerRequestInterface $request):ResponseInterface{
				TestCase::assertSame(['foo3' => 'bar3', 'foo2' => 'bar2', 'foo1' => 'bar1'], $request->getAttributes());
				return new Response(200);
			}
		};
	}

	public function testInvalidMiddlewareException():void{
		$this->expectException(MiddlewareException::class);
		$this->expectExceptionMessage('invalid middleware');

		new QueueDispatcher(['foo']);
	}

}
