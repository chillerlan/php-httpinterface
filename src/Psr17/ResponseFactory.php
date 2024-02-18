<?php
/**
 * Class ResponseFactory
 *
 * @created      27.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr17;

use chillerlan\HTTP\Psr7\Response;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface};

/**
 *
 */
class ResponseFactory implements ResponseFactoryInterface, StatusCodeInterface{

	/**
	 * @inheritDoc
	 */
	public function createResponse(int $code = 200, string $reasonPhrase = ''):ResponseInterface{
		return new Response($code, $reasonPhrase);
	}

}
