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

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\CurlClient;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Client\ClientInterface;

/**
 *
 */
#[Group('slow')]
class CurlClientTest extends HTTPClientTestAbstract{

	protected function initClient():ClientInterface{
		return new CurlClient($this->responseFactory, $this->options);
	}

	public function testRequestError():void{
		$this->expectException(\chillerlan\HTTP\RequestException::class);

		$this->http->sendRequest($this->requestFactory->createRequest('GET', ''));
	}

}
