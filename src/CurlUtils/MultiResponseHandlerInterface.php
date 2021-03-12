<?php
/**
 * Interface MultiResponseHandlerInterface
 *
 * @created      30.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 Smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\CurlUtils;

use Psr\Http\Message\{RequestInterface, ResponseInterface};

interface MultiResponseHandlerInterface{

	/**
	 * The multi response handler. (Schrödingers cat state handler)
	 *
	 * This method will be called within a loop in MultiRequest::processStack().
	 * It may return a RequestInterface object (e.g. as a replacement for a failed request),
	 * which then will be re-added to the running queue, otherwise NULL.
	 *
	 * @param \Psr\Http\Message\ResponseInterface $response    the response
	 * @param \Psr\Http\Message\RequestInterface  $request     the original request
	 * @param int                                 $id          the request ID (order of outgoing requests)
	 * @param array                               $curl_info   curl_info() result for the current request,
	 *                                                         empty array on curl_info() failure
	 *
	 * @return \Psr\Http\Message\RequestInterface|null      an optional replacement request if the previous request failed
	 * @internal
	 */
	public function handleResponse(ResponseInterface $response, RequestInterface $request, int $id, array $curl_info):?RequestInterface;

}
