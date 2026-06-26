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

use chillerlan\HTTP\RequestException;
use chillerlan\HTTPTest\ClientFactories\CurlClientFactory;
use PHPUnit\Framework\Attributes\{Group, Test};

#[Group('slow')]
final class CurlClientTest extends HTTPClientTestAbstract{

	protected string $HTTP_CLIENT_FACTORY = CurlClientFactory::class;

	#[Test]
	public function requestError():void{
		$this->expectException(RequestException::class);

		$this->httpClient->sendRequest($this->requestFactory->createRequest('GET', ''));
	}

}
