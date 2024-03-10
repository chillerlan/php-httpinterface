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

use PHPUnit\Framework\Attributes\Group;
use Psr\Log\AbstractLogger;
use Stringable;
use function date;
use function sprintf;

/**
 *
 */
#[Group('slow')]
class LoggingClientTest extends CurlClientTest{

	protected function setUp():void{
		parent::setUp();

		$logger = new class () extends AbstractLogger{
			public function log($level, string|Stringable $message, array $context = []):void{
				echo sprintf('[%s][%s] %s', date('Y-m-d H:i:s'), $level, 'LoggingClientTest')."\n";
			}
		};
		// we'll just wrap the parent's client
		$this->http = new \chillerlan\HTTP\LoggingClient($this->http, $logger);
	}

	public function testNetworkError():void{
		$this::markTestSkipped('N/A');
	}

	public function testRequestError():void{
		$this::markTestSkipped('N/A');
	}

}