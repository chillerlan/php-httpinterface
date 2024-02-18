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

namespace chillerlan\HTTPTest\Psr18;

use chillerlan\HTTP\HTTPOptions;
use chillerlan\HTTP\Psr7\Request;
use chillerlan\HTTP\Utils\MessageUtil;
use chillerlan\Settings\SettingsContainerInterface;
use Fig\Http\Message\RequestMethodInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\{ClientExceptionInterface, ClientInterface};
use Exception;

/**
 *
 */
abstract class HTTPClientTestAbstract extends TestCase{

	protected const USER_AGENT = 'chillerlanHttpTest/2.0';

	protected HTTPOptions|SettingsContainerInterface $options;
	protected ClientInterface $http;

	protected function setUp():void{

		$this->options = new HTTPOptions([
			'ca_info'    => __DIR__.'/../cacert.pem',
			'user_agent' => $this::USER_AGENT,
		]);

		$this->http = $this->initClient();
	}

	abstract protected function initClient():ClientInterface;

	public function testSendRequest():void{

		try{
			$url      = 'https://httpbin.org/get';
			$response = $this->http->sendRequest(new Request(RequestMethodInterface::METHOD_GET, $url));
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

		$this->http->sendRequest(new Request(RequestMethodInterface::METHOD_GET, 'https://foo'));
	}

}
