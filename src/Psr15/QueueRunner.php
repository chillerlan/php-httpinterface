<?php
/**
 * Class QueueRunner
 *
 * @created      10.03.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr15;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;
use function array_pop;

/**
 * @see https://www.php-fig.org/psr/psr-15/meta/
 */
class QueueRunner implements RequestHandlerInterface{

	/**
	 * QueueRunner constructor.
	 *
	 * @param \Psr\Http\Server\MiddlewareInterface[]   $middlewareStack
	 * @param \Psr\Http\Server\RequestHandlerInterface $fallbackHandler
	 */
	public function __construct(
		protected array                   $middlewareStack,
		protected RequestHandlerInterface $fallbackHandler,
	){

	}

	/**
	 * @inheritDoc
	 */
	public function handle(ServerRequestInterface $request):ResponseInterface{

		if(empty($this->middlewareStack)){
			return $this->fallbackHandler->handle($request);
		}

		return $this->getMiddleware()->process($request, $this);
	}

	/**
	 *
	 */
	protected function getMiddleware():MiddlewareInterface{
		return array_pop($this->middlewareStack);
	}

}
