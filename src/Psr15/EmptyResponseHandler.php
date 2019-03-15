<?php
/**
 * Class EmptyResponseHandler
 *
 * @filesource   EmptyResponseHandler.php
 * @created      09.03.2019
 * @package      chillerlan\HTTP\Psr15
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr15;

use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

class EmptyResponseHandler implements RequestHandlerInterface{

	/**
	 * @var \Psr\Http\Message\ResponseFactoryInterface
	 */
	protected $responseFactory;

	/**
	 * @var int
	 */
	protected $status;

	/**
	 * EmptyResponseHandler constructor.
	 *
	 * @param \Psr\Http\Message\ResponseFactoryInterface $responseFactory
	 * @param int                                        $status
	 */
	public function __construct(ResponseFactoryInterface $responseFactory, int $status){
		$this->responseFactory = $responseFactory;
		$this->status          = $status;
	}

	/**
	 * @inheritDoc
	 */
	public function handle(ServerRequestInterface $request):ResponseInterface{
		return $this->responseFactory->createResponse($this->status);
	}

}
