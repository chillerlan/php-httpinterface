<?php
/**
 * Class RecursiveDispatcherTest
 *
 * @created      15.04.2020
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2020 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr15;

use chillerlan\HTTP\Psr15\{MiddlewareException, RecursiveDispatcher};
use chillerlan\HTTP\Psr7\Response;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

class RecursiveDispatcherTest extends QueueRequestHandlerTest{

	protected function getDispatcher():RequestHandlerInterface{
		$dispatcher = new RecursiveDispatcher($this->getTestFallbackHandler());
		$dispatcher->addStack($this->getTestMiddlewareStack());
		$dispatcher->add($this->getTestMiddleware()); // coverage

		return $dispatcher;
	}

	public function testInvalidMiddlewareException():void{
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
