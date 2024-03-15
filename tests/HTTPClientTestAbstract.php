<?php
/**
 * Class HTTPClientTestAbstract
 *
 * @created      10.11.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\Utils\MessageUtil;
use chillerlan\PHPUnitHttp\HttpFactoryTrait;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\{ClientExceptionInterface};
use Exception, Throwable;
use function realpath;

/**
 *
 */
abstract class HTTPClientTestAbstract extends TestCase{
	use HttpFactoryTrait;

	public const USER_AGENT = 'chillerlanHttpTest/2.0';

	protected function setUp():void{
		// the factories are declared in phpunit.xml, the http clients in their respective tests
		try{
			$this->initFactories(realpath(__DIR__.'/cacert.pem'));
		}
		catch(Throwable $e){
			$this->markTestSkipped('unable to init http factories: '.$e->getMessage());
		}
	}

	public function testSendRequest():void{

		try{
			$url      = 'https://httpbin.org/get';
			$response = $this->httpClient->sendRequest($this->requestFactory->createRequest('GET', $url));
			$json     = MessageUtil::decodeJSON($response);

			$this::assertSame($url, $json->url);
			$this::assertSame($this::USER_AGENT, $json->headers->{'User-Agent'});
			$this::assertSame(200, $response->getStatusCode());
		}
		catch(Exception $e){
			$this->markTestSkipped('error: '.$e->getMessage());
		}

	}

	public function testNetworkError():void{
		$this->expectException(ClientExceptionInterface::class);

		$this->httpClient->sendRequest($this->requestFactory->createRequest('GET', 'https://foo'));
	}

}
