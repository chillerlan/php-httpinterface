<?php
/**
 * Class HTTPClientAbstract
 *
 * @filesource   HTTPClientAbstract.php
 * @created      22.02.2019
 * @package      chillerlan\HTTP\Psr18
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr18;

use chillerlan\HTTP\HTTPOptions;
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

	/**
	 * HTTPClientAbstract constructor.
	 */
	public function __construct(
		SettingsContainerInterface $options = null,
		ResponseFactoryInterface $responseFactory = null,
		LoggerInterface $logger = null
	){
		$this->options         = $options ?? new HTTPOptions;
		$this->responseFactory = $responseFactory ?? new ResponseFactory;
		$this->logger          = $logger ?? new NullLogger;
	}

}
