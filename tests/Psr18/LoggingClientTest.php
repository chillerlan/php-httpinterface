<?php
/**
 * Class LoggingClientTest
 *
 * @filesource   LoggingClientTest.php
 * @created      10.08.2019
 * @package      chillerlan\HTTPTest\Psr18
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr18;

use chillerlan\HTTP\Psr18\LoggingClient;
use Psr\Log\AbstractLogger;

use function date, sprintf;

/**
 * @group slow
 */
class LoggingClientTest extends CurlClientTest{

	protected function setUp():void{
		parent::setUp();

		$logger = new class() extends AbstractLogger{
			public function log($level, $message, array $context = []){
				echo sprintf('[%s][%s] %s', date('Y-m-d H:i:s'), $level, 'LoggingClientTest')."\n";
			}
		};

		$this->http = new LoggingClient($this->http, $logger);
	}

	public function testNetworkError(){
		$this::markTestSkipped('N/A');
	}

	public function testRequestError(){
		$this::markTestSkipped('N/A');
	}

}
