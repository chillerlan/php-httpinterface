<?php
/**
 * Class PriorityQueueDispatcher
 *
 * @link https://github.com/libreworks/caridea-dispatch
 *
 * @filesource   PriorityQueueDispatcher.php
 * @created      10.03.2019
 * @package      chillerlan\HTTP\Psr15
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr15;

use Psr\Http\Server\MiddlewareInterface;

use function usort;

class PriorityQueueDispatcher extends QueueDispatcher{

	/**
	 * @inheritDoc
	 */
	public function addStack(iterable $middlewareStack):QueueDispatcher{

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
	public function add(MiddlewareInterface $middleware):QueueDispatcher{

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
			fn(PriorityMiddlewareInterface $a, PriorityMiddlewareInterface $b) => $b->getPriority() <=> $a->getPriority()
		);
	}

}
