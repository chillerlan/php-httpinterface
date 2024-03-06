<?php
/**
 * Class QueueRequestHandlerTest
 *
 * @created      09.03.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTPTest\Psr15;

use chillerlan\HTTP\Psr15\{MiddlewareException, QueueDispatcher};
use chillerlan\HTTPTest\FactoryTrait;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use function array_keys;

/**
 *
 */
class QueueRequestHandlerTest extends TestCase{
	use FactoryTrait;

	protected function setUp():void{
		$this->initFactories();
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
		$response = $this->getDispatcher()->handle($this->server->createServerRequestFromGlobals());

		$this::assertSame(
			['X-Out-First', 'X-Out-Second', 'X-Out-Third'],
			array_keys($response->getHeaders())
		);
	}

	protected function getTestMiddlewareStack():array{
		return [
			new class () implements MiddlewareInterface{
				public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
					TestCase::assertSame(['foo3' => 'bar3', 'foo2' => 'bar2'], $request->getAttributes());
					$r = $handler->handle($request->withAttribute('foo1', 'bar1'));
					TestCase::assertSame([], array_keys($r->getHeaders()));

					return $r->withHeader('X-Out-First', '1');
				}
			},
			new class () implements MiddlewareInterface{
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
		return new class () implements MiddlewareInterface{
			public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
				TestCase::assertSame([], $request->getAttributes());
				$r = $handler->handle($request->withAttribute('foo3', 'bar3'));
				TestCase::assertSame(['X-Out-First', 'X-Out-Second'], array_keys($r->getHeaders()));

				return $r->withHeader('X-Out-Third', '1');
			}
		};
	}

	protected function getTestFallbackHandler():RequestHandlerInterface{
		return new class ($this->responseFactory) implements RequestHandlerInterface{
			private ResponseFactoryInterface $responseFactory;

			public function __construct(ResponseFactoryInterface $responseFactory){
				$this->responseFactory = $responseFactory;
			}

			public function handle(ServerRequestInterface $request):ResponseInterface{
				TestCase::assertSame(['foo3' => 'bar3', 'foo2' => 'bar2', 'foo1' => 'bar1'], $request->getAttributes());
				return $this->responseFactory->createResponse();
			}
		};
	}

	public function testInvalidMiddlewareException():void{
		$this->expectException(MiddlewareException::class);
		$this->expectExceptionMessage('invalid middleware');

		new QueueDispatcher(['foo']);
	}

}
