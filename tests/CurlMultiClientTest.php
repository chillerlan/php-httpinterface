<?php
/**
 * Class CurlMultiClientTest
 *
 * @created      11.08.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\{CurlMultiClient, HTTPOptions, MultiResponseHandlerInterface};
use chillerlan\HTTP\Utils\QueryUtil;
use chillerlan\PHPUnitHttp\HttpFactoryTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\{RequestInterface, ResponseInterface};
use Throwable;
use function array_column, implode, in_array, ksort;

/**
 *
 */
#[Group('slow')]
final class CurlMultiClientTest extends TestCase{
	use HttpFactoryTrait;

	private CurlMultiClient               $http;
	private MultiResponseHandlerInterface $multiResponseHandler;

	protected function setUp():void{
		$this->initFactories();

		$options = new HTTPOptions([
			'ca_info'        => __DIR__.'/cacert.pem',
			'sleep'          => 750000,
			'dns_over_https' => null,
		]);

		$this->multiResponseHandler = $this->getTestResponseHandler();

		$this->http = new CurlMultiClient($this->multiResponseHandler, $this->responseFactory, $options);
	}

	private function getTestResponseHandler():MultiResponseHandlerInterface{

		return new class () implements MultiResponseHandlerInterface{

			private array $responses = [];

			public function handleResponse(
				ResponseInterface $response,
				RequestInterface  $request,
				int               $id,
				array|null        $curl_info,
			):RequestInterface|null{

				if(in_array($response->getStatusCode(), [200, 206], true)){
					$this->responses[$id]['lang'] = $response->getHeaderLine('content-language');

					// we got the response we expected, return nothing
					return null;
				}

				// return the failed request back to the stack
				return $request;
			}

			public function getResponses():array{
				ksort($this->responses);

				return $this->responses;
			}

		};

	}

	private function getRequests():array{

		$ids = [
			[1, 2, 6, 11, 15, 23, 24, 56, 57, 58, 59, 60, 61, 62, 63, 64, 68, 69, 70, 71, 72, 73, 74, 75, 76],
			[77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101],
		];

		$requests = [];

		foreach($ids as $chunk){
			foreach(['de', 'en', 'es', 'fr', 'zh'] as $lang){
				$requests[] = $this->requestFactory->createRequest(
					'GET',
					'https://api.guildwars2.com/v2/items?'.QueryUtil::build(['lang' => $lang, 'ids' => implode(',', $chunk)])
				);
			}
		}

		return $requests;
	}

	public function testMultiRequest():void{
		$requests = $this->getRequests();

		$this->http
			->addRequests($requests)
			->process()
		;

		// the arenanet API isn't the fastest or most reliable
		try{
			$responses = $this->multiResponseHandler->getResponses();

			$this::assertCount(10, $requests);
			$this::assertCount(10, $responses);
		}
		catch(Throwable){
			$this::markTestSkipped('arenanet API error');
		}

		// the responses are in the same order as the respective requests
		$this::assertSame(['de', 'en', 'es', 'fr', 'zh', 'de', 'en', 'es', 'fr', 'zh'], array_column($responses, 'lang'));

		// cover the destructor
		unset($this->http);
	}

	public function testEmptyStackException():void{
		$this->expectException(ClientExceptionInterface::class);
		$this->expectExceptionMessage('request stack is empty');

		$this->http->process();
	}

}
