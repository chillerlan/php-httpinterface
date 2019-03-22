<?php
/**
 * Class CurlClientNoCATest
 *
 * @filesource   CurlClientNoCATest.php
 * @created      28.08.2018
 * @package      chillerlan\HTTPTest\Psr18
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr18;

use chillerlan\HTTP\{HTTPOptions, Psr18\CurlClient};

class CurlClientNoCATest extends HTTPClientTestAbstract{

	protected function setUp():void{
		$options = new HTTPOptions([
			'ca_info'    => null,
			'user_agent' => $this::USER_AGENT,
		]);

		$this->http = new CurlClient($options);
	}

}
