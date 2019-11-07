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

use chillerlan\HTTP\Psr15\{EmptyResponseHandler, QueueRequestHandler};
use chillerlan\HTTP\Psr15\Middleware\MiddlewareException;
use chillerlan\HTTP\Psr17\ResponseFactory;
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
					$r = $handler->handle($request->withAttribute('foo', 'bar'));
					TestCase::assertTrue($r->hasHeader('X-Out-Second'));

					return $r->withHeader('X-Out-Third', '1');
				}
			},
			new class() implements MiddlewareInterface{
				public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
					TestCase::assertSame('bar', $request->getAttribute('foo'));
					$r = $handler->handle($request->withAttribute('bar', 'baz'));
					TestCase::assertTrue($r->hasHeader('X-Out-First'));

					return $r->withHeader('X-Out-Second', '1');
				}
			},
		];

		// Fallback handler:
		$fallbackHandler = new EmptyResponseHandler(new ResponseFactory, 200);

		// Create request handler instance:
		$handler = new QueueRequestHandler($middlewareStack, $fallbackHandler);

		// coverage
		$handler->add(new class() implements MiddlewareInterface{
			public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
				TestCase::assertSame('baz', $request->getAttribute('bar'));
				$r = $handler->handle($request->withAttribute('baz', 'biz'));

				return $r->withHeader('X-Out-First', '1');
			}
		});

		// execute it:
		$response = $handler->handle(create_server_request_from_globals());

		$this->assertSame(
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
