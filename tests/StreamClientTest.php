<?php
/**
 * Class StreamClientTest
 *
 * @created      23.02.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\StreamClient;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Client\ClientInterface;

/**
 *
 */
#[Group('slow')]
class StreamClientTest extends HTTPClientTestAbstract{

	protected function initClient():ClientInterface{
		return new StreamClient($this->responseFactory, $this->options);
	}

}