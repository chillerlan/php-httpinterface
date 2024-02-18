<?php
/**
 * Class PriorityQueueDispatcher
 *
 * @created      10.03.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTP\Psr15;

use Psr\Http\Server\MiddlewareInterface;
use function usort;

/**
 * @see https://github.com/libreworks/caridea-dispatch
 */
class PriorityQueueDispatcher extends QueueDispatcher{

	/**
	 * @inheritDoc
	 */
	public function addStack(iterable $middlewareStack):static{

		foreach($middlewareStack as $middleware){

			if(!$middleware instanceof MiddlewareInterface){
				throw new MiddlewareException('invalid middleware');
			}

			if(!$middleware instanceof PriorityMiddlewareInterface){
				$middleware = new PriorityMiddleware($middleware);
			}

			$this->middlewareStack[] = $middleware;
		}

		$this->sortMiddleware();

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function add(MiddlewareInterface $middleware):static{

		if(!$middleware instanceof PriorityMiddlewareInterface){
			$middleware = new PriorityMiddleware($middleware);
		}

		$this->middlewareStack[] = $middleware;

		$this->sortMiddleware();

		return $this;
	}

	/**
	 *
	 */
	protected function sortMiddleware():void{
		usort(
			$this->middlewareStack,
			fn(PriorityMiddlewareInterface $a, PriorityMiddlewareInterface $b):int => ($b->getPriority() <=> $a->getPriority())
		);
	}

}
