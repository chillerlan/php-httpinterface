<?php
/**
 * Class RecursiveDispatcherTest
 *
 * @created      15.04.2020
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2020 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTPTest\Psr15;

use chillerlan\HTTP\Psr15\{MiddlewareException, RecursiveDispatcher};
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

/**
 *
 */
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

		$handler = new class ($this->responseFactory) implements RequestHandlerInterface{
			private ResponseFactoryInterface $responseFactory;

			public function __construct(ResponseFactoryInterface $responseFactory){
				$this->responseFactory = $responseFactory;
			}

			public function handle(ServerRequestInterface $request):ResponseInterface{
				return $this->responseFactory->createResponse();
			}
		};

		$dispatcher = new RecursiveDispatcher($handler);
		$dispatcher->addStack(['foo']);
	}

}
