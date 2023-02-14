<?php
/**
 * Class HTTPClientTest
 *
 * @created      28.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr18;

use chillerlan\HTTP\Psr18\{CurlClient, RequestException};
use chillerlan\HTTP\Psr7\Request;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientInterface;

/**
 * @group slow
 */
class CurlClientTest extends HTTPClientTestAbstract{

	protected function initClient():ClientInterface{
		return new CurlClient($this->options);
	}

	public function testRequestError():void{
		$this->expectException(RequestException::class);

		$this->http->sendRequest(new Request(RequestMethodInterface::METHOD_GET, ''));
	}

}
