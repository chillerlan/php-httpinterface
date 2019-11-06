<?php
/**
 * Class ServerRequestFactoryTest
 *
 * @filesource   ServerRequestFactoryTest.php
 * @created      06.11.2019
 * @package      chillerlan\HTTPTest\Psr17
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr17;

use chillerlan\HTTP\Psr17\ServerRequestFactory;
use PHPUnit\Framework\TestCase;

class ServerRequestFactoryTest extends TestCase{

	/**
	 * @var \chillerlan\HTTP\Psr17\ServerRequestFactory
	 */
	protected $serverRequestFactory;

	protected function setUp():void{
		$this->serverRequestFactory = new ServerRequestFactory;
	}

	// coverage
	public function testCookieParams(){
		$r = $this->serverRequestFactory
			->createServerRequest($this->serverRequestFactory::METHOD_GET, '/');

		$this->assertSame('/', $r->getUri()->__toString());
	}

}
