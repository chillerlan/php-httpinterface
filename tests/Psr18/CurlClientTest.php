<?php
/**
 * Class HTTPClientTest
 *
 * @created      28.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTPTest\Psr18;

use chillerlan\HTTP\Psr18\{CurlClient, RequestException};
use chillerlan\HTTP\Psr7\Request;
use Fig\Http\Message\RequestMethodInterface;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Client\ClientInterface;

/**
 *
 */
#[Group('slow')]
class CurlClientTest extends HTTPClientTestAbstract{

	protected function initClient():ClientInterface{
		return new CurlClient($this->options);
	}

	public function testRequestError():void{
		$this->expectException(RequestException::class);

		$this->http->sendRequest(new Request(RequestMethodInterface::METHOD_GET, ''));
	}

}
