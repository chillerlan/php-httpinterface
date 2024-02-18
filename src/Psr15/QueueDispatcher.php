<?php
/**
 * Class QueueDispatcher
 *
 * @created      08.03.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTP\Psr15;

use chillerlan\HTTP\Psr17\ResponseFactory;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

/**
 * @see https://github.com/libreworks/caridea-dispatch
 */
class QueueDispatcher implements MiddlewareInterface, RequestHandlerInterface{

	/** @var \Psr\Http\Server\MiddlewareInterface[] */
	protected array                   $middlewareStack = [];
	protected RequestHandlerInterface $fallbackHandler;

	/**
	 * QueueDispatcher constructor.
	 */
	public function __construct(
		iterable|null                $middlewareStack = null,
		RequestHandlerInterface|null $fallbackHandler = null,
	){
		$fallbackHandler ??= new EmptyResponseHandler(new ResponseFactory, 500);

		$this
			->addStack(($middlewareStack ?? []))
			->setFallbackHandler($fallbackHandler)
		;
	}

	/**
	 *
	 */
	public function setFallbackHandler(RequestHandlerInterface $fallbackHandler):static{
		$this->fallbackHandler = $fallbackHandler;

		return $this;
	}

	/**
	 * @throws \chillerlan\HTTP\Psr15\MiddlewareException
	 */
	public function addStack(iterable $middlewareStack):static{

		foreach($middlewareStack as $middleware){

			if(!$middleware instanceof MiddlewareInterface){
				throw new MiddlewareException('invalid middleware');
			}

			$this->middlewareStack[] = $middleware;
		}

		return $this;
	}

	/**
	 *
	 */
	public function add(MiddlewareInterface $middleware):static{
		$this->middlewareStack[] = $middleware;

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function handle(ServerRequestInterface $request):ResponseInterface{
		return $this->getRunner($this->fallbackHandler)->handle($request);
	}

	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
		return $this->getRunner($handler)->handle($request);
	}

	/**
	 *
	 */
	protected function getRunner(RequestHandlerInterface $handler):QueueRunner{
		return new QueueRunner($this->middlewareStack, $handler);
	}

}
