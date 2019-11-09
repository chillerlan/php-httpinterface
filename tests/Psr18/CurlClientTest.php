<?php
/**
 * Class HTTPClientTest
 *
 * @filesource   HTTPClientTest.php
 * @created      28.08.2018
 * @package      chillerlan\HTTPTest\Psr18
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr18;

use chillerlan\HTTP\Psr18\{CurlClient, RequestException};
use chillerlan\HTTP\Psr7\Request;

class CurlClientTest extends HTTPClientTestAbstract{

	protected function setUp():void{
		parent::setUp();

		$this->http = new CurlClient($this->options);
	}

	public function testRequestError(){
		$this->expectException(RequestException::class);

		$this->http->sendRequest(new Request(Request::METHOD_GET, ''));
	}

}
