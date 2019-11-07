<?php
/**
 * Class QueueRequestHandler
 *
 * @link https://github.com/libreworks/caridea-dispatch
 *
 * @filesource   QueueRequestHandler.php
 * @created      08.03.2019
 * @package      chillerlan\HTTP\Psr15
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr15;

use chillerlan\HTTP\Psr17\ResponseFactory;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class QueueRequestHandler implements MiddlewareInterface, RequestHandlerInterface{

	/**
	 * @var \Psr\Http\Server\MiddlewareInterface[]
	 */
	protected $middlewareStack = [];

	/**
	 * @var \Psr\Http\Server\RequestHandlerInterface
	 */
	protected $fallbackHandler;

	/**
	 * QueueRequestHandler constructor.
	 *
	 * @param iterable|null                                 $middlewareStack
	 * @param \Psr\Http\Server\RequestHandlerInterface|null $fallbackHandler
	 */
	public function __construct(iterable $middlewareStack = null, RequestHandlerInterface $fallbackHandler = null){
		$this->fallbackHandler = $fallbackHandler ?? new EmptyResponseHandler(new ResponseFactory, 500);

		$this->addStack($middlewareStack ?? []);
	}

	/**
	 * @param iterable|\Psr\Http\Server\MiddlewareInterface[] $middlewareStack
	 *
	 * @return \chillerlan\HTTP\Psr15\QueueRequestHandler
	 * @throws \Exception
	 */
	public function addStack(iterable $middlewareStack):QueueRequestHandler{

		foreach($middlewareStack as $middleware){

			if(!$middleware instanceof MiddlewareInterface){
				throw new MiddlewareException('invalid middleware');
			}

			$this->middlewareStack[] = $middleware;
		}

		return $this;
	}

	/**
	 * @param \Psr\Http\Server\MiddlewareInterface $middleware
	 *
	 * @return \chillerlan\HTTP\Psr15\QueueRequestHandler
	 */
	public function add(MiddlewareInterface $middleware):QueueRequestHandler{
		$this->middlewareStack[] = $middleware;

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function handle(ServerRequestInterface $request):ResponseInterface{
		return (new QueueRunner($this->middlewareStack, $this->fallbackHandler))->handle($request);
	}

	/**
	 * @inheritDoc
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler):ResponseInterface{
		return (new QueueRunner($this->middlewareStack, $handler))->handle($request);
	}

}
