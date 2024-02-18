<?php
/**
 * Class RecursiveDispatcher
 *
 * A simple middleware dispatcher based on Slim
 *
 * @see          https://github.com/slimphp/Slim/blob/de07f779d229ec06080259a816b0740de830438c/Slim/MiddlewareDispatcher.php
 *
 * @created      15.04.2020
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2020 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr15;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class RecursiveDispatcher implements RequestHandlerInterface{

	/**
	 * RecursiveDispatcher constructor.
	 */
	public function __construct(
		protected RequestHandlerInterface $kernel,
	){

	}

	/**
	 * Add a new middleware to the stack
	 *
	 * Middleware are organized as a stack. That means middleware
	 * that have been added before will be executed after the newly
	 * added one (last in, first out).
	 */
	public function add(MiddlewareInterface $middleware):static{

		$this->kernel = new class ($middleware, $this->kernel) implements RequestHandlerInterface{

			public function __construct(
				private MiddlewareInterface     $middleware,
				private RequestHandlerInterface $next,
			){

			}

			public function handle(ServerRequestInterface $request):ResponseInterface{
				return $this->middleware->process($request, $this->next);
			}

		};

		return $this;
	}

	/**
	 * @param \Psr\Http\Server\MiddlewareInterface[] $middlewareStack
	 *
	 * @throws \chillerlan\HTTP\Psr15\MiddlewareException
	 */
	public function addStack(iterable $middlewareStack):static{

		foreach($middlewareStack as $middleware){

			if(!$middleware instanceof MiddlewareInterface){
				throw new MiddlewareException('invalid middleware');
			}

			$this->add($middleware);
		}

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function handle(ServerRequestInterface $request):ResponseInterface{
		return $this->kernel->handle($request);
	}

}
