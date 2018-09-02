<?php
/**
 * Class ResponseFactory
 *
 * @filesource   ResponseFactory.php
 * @created      27.08.2018
 * @package      chillerlan\HTTP\Psr17
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr17;

use chillerlan\HTTP\Psr7\Response;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface};

final class ResponseFactory implements ResponseFactoryInterface, StatusCodeInterface{

	/**
	 * Create a new response.
	 *
	 * @param int    $code         HTTP status code; defaults to 200
	 * @param string $reasonPhrase Reason phrase to associate with status code
	 *                             in generated response; if none is provided implementations MAY use
	 *                             the defaults as suggested in the HTTP specification.
	 *
	 * @return \Psr\Http\Message\ResponseInterface|\chillerlan\HTTP\Psr7\Response
	 */
	public function createResponse(int $code = 200, string $reasonPhrase = ''):ResponseInterface{
		return new Response($code, null, null, null, $reasonPhrase);
	}

}
