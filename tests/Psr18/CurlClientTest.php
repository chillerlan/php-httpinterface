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

use chillerlan\HTTP\{HTTPOptions, Psr18\CurlClient, Psr18\RequestException, Psr7\Request};

class CurlClientTest extends HTTPClientTestAbstract{

	protected function setUp():void{
		$options = new HTTPOptions([
			'ca_info' => __DIR__.'/../cacert.pem',
			'user_agent' => $this::USER_AGENT,
		]);

		$this->http = new CurlClient($options);
	}

	public function testRequestError(){
		$this->expectException(RequestException::class);

		$this->http->sendRequest(new Request(Request::METHOD_GET, ''));
	}

}
