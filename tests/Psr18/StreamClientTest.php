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
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Client\ClientInterface;

/**
 *
 */
#[Group('slow')]
class StreamClientTest extends HTTPClientTestAbstract{

	protected function initClient():ClientInterface{
		return new StreamClient($this->options);
	}

}
