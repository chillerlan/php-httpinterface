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

use chillerlan\HTTP\Utils\MessageUtil;
use Psr\Http\Client\{ClientExceptionInterface, ClientInterface};
use Psr\Http\Message\{RequestInterface, ResponseInterface};
use Psr\Log\{LoggerAwareInterface, LoggerAwareTrait, LoggerInterface, NullLogger};
use Throwable;
use function get_class, sprintf;

/**
 * @codeCoverageIgnore
 */
class LoggingClient implements ClientInterface, LoggerAwareInterface{
	use LoggerAwareTrait;

	protected ClientInterface $http;

	/**
	 * LoggingClient constructor.
	 */
	public function __construct(ClientInterface $http, LoggerInterface $logger = null){
		$this->http   = $http;
		$this->logger = ($logger ?? new NullLogger);
	}

	/**
	 * @inheritDoc
	 */
	public function sendRequest(RequestInterface $request):ResponseInterface{
		$this->logger->debug(sprintf("\n----HTTP-REQUEST----\n%s", MessageUtil::toString($request)));

		try{
			$response = $this->http->sendRequest($request);

			$this->logger->debug(sprintf("\n----HTTP-RESPONSE---\n%s", MessageUtil::toString($response)));
		}
		catch(Throwable $e){
			$this->logger->error($e->getMessage());
			$this->logger->error($e->getTraceAsString());

			if(!$e instanceof ClientExceptionInterface){
				throw new ClientException(sprintf('unexpected exception, does not implement "ClientExceptionInterface": %s', get_class($e)));
			}

			throw $e;
		}

		return $response;
	}

}
