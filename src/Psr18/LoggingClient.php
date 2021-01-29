<?php
/**
 * Class LoggingClient
 *
 * a silly logging wrapper (do not use in production!)
 *
 * @created      07.08.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr18;

use Psr\Http\Client\{ClientExceptionInterface, ClientInterface};
use Psr\Http\Message\{RequestInterface, ResponseInterface};
use Psr\Log\{LoggerAwareInterface, LoggerAwareTrait, LoggerInterface, NullLogger};
use Throwable;

use function chillerlan\HTTP\Psr7\message_to_string;
use function get_class;

class LoggingClient implements ClientInterface, LoggerAwareInterface{
	use LoggerAwareTrait;

	protected ClientInterface $http;

	/**
	 * LoggingClient constructor.
	 */
	public function __construct(ClientInterface $http, LoggerInterface $logger = null){
		$this->http   = $http;
		$this->logger = $logger ?? new NullLogger;
	}

	/**
	 * @inheritDoc
	 */
	public function sendRequest(RequestInterface $request):ResponseInterface{
		$this->logger->debug("\n----HTTP-REQUEST----\n".message_to_string($request));

		try{
			$response = $this->http->sendRequest($request);
		}
		catch(Throwable $e){
			$this->logger->debug("\n----HTTP-ERROR------\n".message_to_string($request));
			$this->logger->error($e->getMessage());
			$this->logger->error($e->getTraceAsString());

			if(!$e instanceof ClientExceptionInterface){
				throw new ClientException('unexpected exception, does not implement "ClientExceptionInterface": '.get_class($e));  // @codeCoverageIgnore
			}

			throw $e;
		}

		$this->logger->debug("\n----HTTP-RESPONSE---\n".message_to_string($response));

		return $response;
	}

}
