<?php
/**
 * Class StreamClientTest
 *
 * @filesource   StreamClientTest.php
 * @created      21.10.2017
 * @package      chillerlan\HTTPTest
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\StreamClient;
use chillerlan\TinyCurl\Request;

class StreamClientTest extends HTTPClientTestAbstract{

	protected function setUp(){
		parent::setUp();

		$this->http = new StreamClient($this->options);
	}

	/**
	 * @expectedException \chillerlan\HTTP\HTTPClientException
	 * @expectedExceptionMessage invalid CA file
	 */
	public function testNoCAException(){
		new StreamClient($this->getOptions());
	}

}
