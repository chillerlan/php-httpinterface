<?php
/**
 * Class CurlClientTest
 *
 * @filesource   CurlClientTest.php
 * @created      21.10.2017
 * @package      chillerlan\HTTPTest
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\CurlClient;

class CurlClientTest extends HTTPClientTestAbstract{

	protected function setUp(){
		parent::setUp();

		$this->http = new CurlClient($this->options);

	}

	/**
	 * @expectedException \chillerlan\HTTP\HTTPClientException
	 * @expectedExceptionMessage invalid CA file
	 */
	public function testNoCAException(){
		$this->http = new CurlClient($this->getOptions());
	}

}
