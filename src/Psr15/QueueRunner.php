<?php
/**
 * Class QueueRunner
 *
 * @link https://www.php-fig.org/psr/psr-15/meta/
 *
 * @filesource   QueueRunner.php
 * @created      10.03.2019
 * @package      chillerlan\HTTP\Psr15
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr15;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

use function array_shift;

class QueueRunner implements RequestHandlerInterface{

	/**
	 * @var \Psr\Http\Server\MiddlewareInterface[]
	 */
	private array $middlewareStack;

	/**
	 * @var \Psr\Http\Server\RequestHandlerInterface
	 */
	private RequestHandlerInterface $fallbackHandler;

	/**
	 *  constructor.
	 *
	 * @param array                                    $middlewareStack
	 * @param \Psr\Http\Server\RequestHandlerInterface $fallbackHandler
	 */
	public function __construct(array $middlewareStack, RequestHandlerInterface $fallbackHandler){
		$this->middlewareStack = $middlewareStack;
		$this->fallbackHandler = $fallbackHandler;
	}

	/**
	 * @inheritDoc
	 */
	public function handle(ServerRequestInterface $request):ResponseInterface{

		if(empty($this->middlewareStack)){
			return $this->fallbackHandler->handle($request);
		}

		$middleware = array_shift($this->middlewareStack);

		return $middleware->process($request, $this);
	}

}
