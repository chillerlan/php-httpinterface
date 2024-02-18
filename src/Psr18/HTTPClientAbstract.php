<?php
/**
 * Class HTTPClientAbstract
 *
 * @created      22.02.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr18;

use chillerlan\HTTP\HTTPOptions;
use chillerlan\HTTP\Psr17\{ResponseFactory};
use chillerlan\Settings\SettingsContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\{LoggerInterface, NullLogger};

abstract class HTTPClientAbstract implements HTTPClientInterface{

	protected StreamFactoryInterface|null $streamFactory = null;

	/**
	 * HTTPClientAbstract constructor.
	 */
	public function __construct(
		protected HTTPOptions|SettingsContainerInterface $options = new HTTPOptions,
		protected ResponseFactoryInterface               $responseFactory = new ResponseFactory,
		protected LoggerInterface                        $logger = new NullLogger,
	){

	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function setLogger(LoggerInterface $logger):static{
		$this->logger = $logger;

		return $this;
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function setResponseFactory(ResponseFactoryInterface $responseFactory):static{
		$this->responseFactory = $responseFactory;

		return $this;
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function setStreamFactory(StreamFactoryInterface $streamFactory):static{
		$this->streamFactory = $streamFactory;

		return $this;
	}

}
