<?php
/**
 * Class TinyCurlClientTest
 *
 * @filesource   TinyCurlClientTest.php
 * @created      21.10.2017
 * @package      chillerlan\HTTPTest
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\TinyCurlClient;
use chillerlan\TinyCurl\Request;

class TinyCurlClientTest extends HTTPClientTestAbstract{

	protected function setUp(){
		parent::setUp();
		$this->http = new TinyCurlClient($this->options, new Request($this->options));
	}

}
