<?php
/**
 * Class PriorityMiddleware
 *
 * @filesource   PriorityMiddleware.php
 * @created      10.03.2019
 * @package      chillerlan\HTTP\Psr15
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr15;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

use const PHP_INT_MIN;

class PriorityMiddleware implements PriorityMiddlewareInterface{

	/**
	 * @var \Psr\Http\Server\MiddlewareInterface
	 */
	protected MiddlewareInterface $middleware;

	/**
	 * @var int
	 */
	protected int $priority;

	/**
	 * PriorityMiddleware constructor.
	 *
	 * @param \Psr\Http\Server\MiddlewareInterface $middleware
	 * @param int|null                             $priority
	 */
	public function __construct(MiddlewareInterface $middleware, int $priority = null){
		$this->middleware = $middleware;
		$this->priority   = $priority ?? PHP_INT_MIN;
	}

	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
		return $this->middleware->process($request, $handler);
	}

	/**
	 * @inheritDoc
	 */
	public function getPriority():int{
		return $this->priority;
	}

}
