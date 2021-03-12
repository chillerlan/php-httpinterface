<?php
/**
 * Class URLExtractor
 *
 * @created      15.08.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr18;

use chillerlan\HTTP\Psr7\Request;
use Psr\Http\Message\{RequestInterface, ResponseInterface};

use function in_array;

class URLExtractor extends CurlClient{

	/**
	 * @var \Psr\Http\Message\ResponseInterface[]
	 */
	protected array $responses = [];

	/**
	 * @inheritDoc
	 */
	public function sendRequest(RequestInterface $request):ResponseInterface{

		do{
			$response = parent::sendRequest($request);
			$request  = new Request($request->getMethod(), $response->getHeaderLine('location'));
			$this->responses[] = $response;
		}
		while(in_array($response->getStatusCode(), [301, 302, 303, 307, 308], true));

		return $response;
	}

	/**
	 * @return \Psr\Http\Message\ResponseInterface[]
	 */
	public function getResponses():array{
		return $this->responses;
	}

}
