<?php
/**
 * Class StreamClientNoCATest
 *
 * @filesource   StreamClientNoCATest.php
 * @created      23.02.2019
 * @package      chillerlan\HTTPTest\Psr18
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr18;

use chillerlan\HTTP\Psr18\StreamClient;

/**
 * @group slow
 */
class StreamClientNoCATest extends HTTPClientTestAbstract{

	protected function setUp():void{
		parent::setUp();

		$this->options->ca_info = null;
		$this->options->ssl_verifypeer = false;

		$this->http = new StreamClient($this->options);
	}

}
