<?php
/**
 * Class PriorityQueueRequestHandler
 *
 * @link https://github.com/libreworks/caridea-dispatch
 *
 * @filesource   PriorityQueueRequestHandler.php
 * @created      10.03.2019
 * @package      chillerlan\HTTP\Psr15
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr15;

use Psr\Http\Server\MiddlewareInterface;

class PriorityQueueRequestHandler extends QueueRequestHandler{

	/**
	 * @inheritDoc
	 */
	public function addStack(iterable $middlewareStack):QueueRequestHandler{

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
	public function add(MiddlewareInterface $middleware):QueueRequestHandler{

		if(!$middleware instanceof PriorityMiddlewareInterface){
			$middleware = new PriorityMiddleware($middleware);
		}

		$this->middlewareStack[] = $middleware;

		$this->sortMiddleware();

		return $this;
	}

	/**
	 * @return void
	 */
	protected function sortMiddleware():void{
		\usort($this->middlewareStack, function(PriorityMiddlewareInterface $a, PriorityMiddlewareInterface $b){
			return $a->getPriority() < $b->getPriority();
		});
	}

}
