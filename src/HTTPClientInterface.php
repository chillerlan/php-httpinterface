<?php
/**
 * Interface HTTPClientInterface
 *
 * @created      14.02.2023
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2023 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTP;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\{ResponseFactoryInterface, StreamFactoryInterface};
use Psr\Log\LoggerInterface;

/**
 *
 */
interface HTTPClientInterface extends ClientInterface{

	/**
	 * Sets a PSR-3 Logger
	 */
	public function setLogger(LoggerInterface $logger):static;

	/**
	 * Sets a PSR-17 response factory
	 */
	public function setResponseFactory(ResponseFactoryInterface $responseFactory):static;

	/**
	 * Sets a PSR-17 stream factory
	 */
	public function setStreamFactory(StreamFactoryInterface $streamFactory):static;

}
