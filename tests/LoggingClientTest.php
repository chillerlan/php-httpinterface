<?php
/**
 * Class LoggingClientTest
 *
 * @created      10.08.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\{CurlClient, LoggingClient};
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Client\ClientInterface;
use Psr\Log\AbstractLogger;
use Stringable;
use function date, printf;

/**
 *
 */
#[Group('slow')]
#[Group('output')]
class LoggingClientTest extends HTTPClientTestAbstract{

	protected function initClient():ClientInterface{
		$this->options->ssl_verifypeer = false; // whyyy???

		$http   = new CurlClient($this->responseFactory, $this->options);
		$logger = new class () extends AbstractLogger{
			public function log($level, string|Stringable $message, array $context = []):void{
				printf("\n[%s][%s] LoggingClientTest: %s", date('Y-m-d H:i:s'), $level, $message);
			}
		};

		return new LoggingClient($http, $logger);
	}

	public function testNetworkError():void{
		$this::markTestSkipped('N/A');
	}

	public function testRequestError():void{
		$this::markTestSkipped('N/A');
	}

}
