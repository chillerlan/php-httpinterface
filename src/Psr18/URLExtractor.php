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

use chillerlan\HTTP\Psr17\RequestFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\{RequestFactoryInterface, RequestInterface, ResponseInterface, UriInterface};
use function count;
use function in_array;

/**
 * A client that follows redirects until it reaches a non-30x response, e.g. to extract shortened URLs
 *
 * The given HTTP client needs to be set up accordingly:
 *
 *   - CURLOPT_FOLLOWLOCATION  must be set to false so that we can intercept the 30x responses
 *   - CURLOPT_MAXREDIRS       should be set to a value > 1
 */
class URLExtractor implements ClientInterface{

	/** @var \Psr\Http\Message\ResponseInterface[] */
	protected array $responses = [];

	protected ClientInterface $http;

	protected RequestFactoryInterface $requestFactory;

	/**
	 * URLExtractor constructor.
	 */
	public function __construct(ClientInterface $http, RequestFactoryInterface $requestFactory = null){
		$this->http           = $http;
		$this->requestFactory = $requestFactory ?? new RequestFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function sendRequest(RequestInterface $request):ResponseInterface{

		do{
			// fetch the response for the current request
			$response          = $this->http->sendRequest($request);
			$this->responses[] = $response;
			// set up a new request to the location header of the last response
			$request           = $this->requestFactory->createRequest($request->getMethod(), $response->getHeaderLine('location'));
		}
		while(in_array($response->getStatusCode(), [301, 302, 303, 307, 308], true));

		return $response;
	}

	/**
	 * extract the given URL and return the last valid location header
	 */
	public function extract(UriInterface|string $shortURL):?string{
		$request  = $this->requestFactory->createRequest('GET', $shortURL);
		$response = $this->sendRequest($request);

		if($response->getStatusCode() !== 200 || empty($this->responses)){
			return null;
		}

		$count = count($this->responses) - 2;

		while($count >= 0){

			$url = $this->responses[$count]?->getHeaderLine('location');

			if(!empty($url)){
				return $url;
			}

			$count--;
		}

		return null;
	}

	/**
	 * @return \Psr\Http\Message\ResponseInterface[]
	 */
	public function getResponses():array{
		return $this->responses;
	}

}
