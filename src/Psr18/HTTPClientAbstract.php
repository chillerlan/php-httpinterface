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

	protected HTTPOptions|SettingsContainerInterface $options;
	protected LoggerInterface                        $logger;
	protected ResponseFactoryInterface               $responseFactory;
	protected ?StreamFactoryInterface                $streamFactory   = null;

	/**
	 * HTTPClientAbstract constructor.
	 */
	public function __construct(
		HTTPOptions|SettingsContainerInterface $options = null,
		ResponseFactoryInterface $responseFactory = null,
		LoggerInterface $logger = null
	){
		$this->options = $options ?? new HTTPOptions;

		$this
			->setResponseFactory($responseFactory ?? new ResponseFactory)
			->setLogger($logger ?? new NullLogger)
		;
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function setLogger(LoggerInterface $logger):HTTPClientInterface{
		$this->logger = $logger;

		return $this;
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function setResponseFactory(ResponseFactoryInterface $responseFactory):HTTPClientInterface{
		$this->responseFactory = $responseFactory;

		return $this;
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
	public function setStreamFactory(StreamFactoryInterface $streamFactory):HTTPClientInterface{
		$this->streamFactory = $streamFactory;

		return $this;
	}

}
