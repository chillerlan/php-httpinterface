<?php
/**
 * Class EmptyResponseHandler
 *
 * @created      09.03.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr15;

use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

class EmptyResponseHandler implements RequestHandlerInterface{

	/**
	 * EmptyResponseHandler constructor.
	 */
	public function __construct(
		protected ResponseFactoryInterface $responseFactory,
		protected int                      $status,
	){

	}

	/**
	 * @inheritDoc
	 */
	public function handle(ServerRequestInterface $request):ResponseInterface{
		return $this->responseFactory->createResponse($this->status);
	}

}
