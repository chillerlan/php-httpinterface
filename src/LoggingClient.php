<?php
/**
 * Class LoggingClient
 *
 * @created      07.08.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTP;

use chillerlan\HTTP\Utils\MessageUtil;
use Psr\Http\Client\{ClientExceptionInterface, ClientInterface};
use Psr\Http\Message\{RequestInterface, ResponseInterface};
use Psr\Log\{LoggerInterface, NullLogger};
use Throwable;
use function get_class, sprintf;

/**
 * a silly logging wrapper (do not use in production!)
 *
 * @codeCoverageIgnore
 */
class LoggingClient implements ClientInterface{

	/**
	 * LoggingClient constructor.
	 */
	public function __construct(
		protected ClientInterface $http,
		protected LoggerInterface $logger = new NullLogger,
	){

	}

	/**
	 * @codeCoverageIgnore
	 */
	public function setLogger(LoggerInterface $logger):static{
		$this->logger = $logger;

		return $this;
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
				throw new ClientException(
					sprintf('unexpected exception, does not implement "ClientExceptionInterface": %s', get_class($e)),
				);
			}

			throw $e;
		}

		return $response;
	}

}