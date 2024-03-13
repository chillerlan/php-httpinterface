<?php
/**
 * Class HTTPOptionsTest
 *
 * @created      14.11.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\{CurlHandle, HTTPOptions};
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use function realpath;
use const CURLOPT_CAINFO, CURLOPT_CAPATH, CURLOPT_SSL_VERIFYHOST, CURLOPT_SSL_VERIFYPEER;

/**
 *
 */
class HTTPOptionsTest extends TestCase{
	use FactoryTrait;

	protected function setUp():void{
		$this->initFactories();
	}

	protected function createTestHandleOptions(HTTPOptions $options):array{
		$response = $this->responseFactory->createResponse();

		$ch = new CurlHandle($this->requestFactory->createRequest('GET', 'https://example.com'), $response, $options);
		$ch->init();

		return $ch->getCurlOptions();
	}

	public function testInvalidUserAgentException():void{
		$this->expectException(ClientExceptionInterface::class);
		$this->expectExceptionMessage('invalid user agent');

		new HTTPOptions(['user_agent' => '']);
	}

}
