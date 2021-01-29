<?php
/**
 * Class StreamClientTest
 *
 * @created      23.02.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr18;

use chillerlan\HTTP\Psr18\StreamClient;

/**
 * @group slow
 */
class StreamClientTest extends HTTPClientTestAbstract{

	protected function setUp():void{
		parent::setUp();

		$this->http = new StreamClient($this->options);
	}

}
