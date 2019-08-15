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

	/**
	 * @dataProvider requestDataProvider
	 *
	 * @param $method
	 * @param $extra_headers
	 */
	public function testRequest(string $method, array $extra_headers){
		$this->markTestSkipped('N/A');
	}

}
