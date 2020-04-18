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

use chillerlan\HTTP\Psr18\CurlClient;

/**
 * @group slow
 */
class CurlClientNoCATest extends HTTPClientTestAbstract{

	protected function setUp():void{
		parent::setUp();

		$this->options->ca_info = null;
		$this->options->ssl_verifypeer = false;

		$this->http = new CurlClient($this->options);
	}

}
