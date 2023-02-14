<?php
/**
 * Class QueueDispatcher
 *
 * @link         https://github.com/libreworks/caridea-dispatch
 *
 * @created      08.03.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr15;

use chillerlan\HTTP\Psr17\ResponseFactory;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class QueueDispatcher implements MiddlewareInterface, RequestHandlerInterface{

	/** @var \Psr\Http\Server\MiddlewareInterface[] */
	protected array                   $middlewareStack = [];
	protected RequestHandlerInterface $fallbackHandler;

	/**
	 * QueueDispatcher constructor.
	 */
	public function __construct(iterable $middlewareStack = null, RequestHandlerInterface $fallbackHandler = null){
		$this
			->addStack($middlewareStack ?? [])
			->setFallbackHandler($fallbackHandler ?? new EmptyResponseHandler(new ResponseFactory, 500))
		;
	}

	/**
	 *
	 */
	public function setFallbackHandler(RequestHandlerInterface $fallbackHandler):QueueDispatcher{
		$this->fallbackHandler = $fallbackHandler;

		return $this;
	}

	/**
	 * @throws \chillerlan\HTTP\Psr15\MiddlewareException
	 */
	public function addStack(iterable $middlewareStack):QueueDispatcher{

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
	public function add(MiddlewareInterface $middleware):QueueDispatcher{
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
