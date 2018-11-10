<?php
/**
 * Class HTTPClientTest
 *
 * @filesource   HTTPClientTest.php
 * @created      28.08.2018
 * @package      chillerlan\HTTPTest
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Client;

use chillerlan\HTTP\{CurlClient, HTTPOptions};

class CurlClientTest extends HTTPClientTestAbstract{

	protected function setUp(){
		$options = new HTTPOptions([
			'ca_info' => __DIR__.'/../cacert.pem',
			'user_agent' => $this::USER_AGENT,
		]);

		$this->http = new CurlClient($options);
	}

}
