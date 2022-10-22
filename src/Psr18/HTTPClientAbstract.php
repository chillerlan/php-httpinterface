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
use Psr\Http\Message\StreamFactoryInterface;
use chillerlan\HTTP\Psr17\{ResponseFactory};
use chillerlan\Settings\SettingsContainerInterface;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\{LoggerAwareInterface, LoggerAwareTrait, LoggerInterface, NullLogger};

abstract class HTTPClientAbstract implements ClientInterface, LoggerAwareInterface, RequestMethodInterface{
	use LoggerAwareTrait;

	/** @var \chillerlan\Settings\SettingsContainerInterface|\chillerlan\HTTP\HTTPOptions */
	protected SettingsContainerInterface $options;

	protected ResponseFactoryInterface $responseFactory;
	protected ?StreamFactoryInterface $streamFactory = null;

	/**
	 * HTTPClientAbstract constructor.
	 */
	public function __construct(
		SettingsContainerInterface $options = null,
		ResponseFactoryInterface $responseFactory = null,
		LoggerInterface $logger = null
	){
		$this->options = $options ?? new HTTPOptions;

		$this->setResponseFactory($responseFactory ?? new ResponseFactory);
		$this->setLogger($logger ?? new NullLogger);
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function setResponseFactory(ResponseFactoryInterface $responseFactory):self{
		$this->responseFactory = $responseFactory;

		return $this;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function setStreamFactory(StreamFactoryInterface $streamFactory):self{
		$this->streamFactory = $streamFactory;

		return $this;
	}

}
