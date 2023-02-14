<?php
/**
 * Class StreamClientNoCATest
 *
 * @created      23.02.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr18;

use chillerlan\HTTP\Psr18\StreamClient;
use Psr\Http\Client\ClientInterface;

/**
 * @group slow
 */
class StreamClientNoCATest extends HTTPClientTestAbstract{

	protected function initClient():ClientInterface{
		$this->options->ca_info        = null;
		$this->options->ssl_verifypeer = false;

		return new StreamClient($this->options);
	}

}
