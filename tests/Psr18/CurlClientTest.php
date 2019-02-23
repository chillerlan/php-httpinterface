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

use chillerlan\HTTP\{HTTPOptions, Psr18\CurlClient};

class CurlClientTest extends HTTPClientTestAbstract{

	protected function setUp(){
		$options = new HTTPOptions([
			'ca_info' => __DIR__.'/../cacert.pem',
			'user_agent' => $this::USER_AGENT,
		]);

		$this->http = new CurlClient($options);
	}

}
