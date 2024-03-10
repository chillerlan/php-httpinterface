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

use chillerlan\HTTP\{CurlMultiClient, MultiResponseHandlerInterface};
use chillerlan\HTTP\HTTPOptions;
use chillerlan\HTTP\Utils\QueryUtil;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\{RequestInterface, ResponseInterface};
use function array_column, defined, implode, in_array, ksort;

/**
 *
 */
#[Group('slow')]
class CurlMultiClientTest extends TestCase{
	use FactoryTrait;

	protected CurlMultiClient               $http;
	protected MultiResponseHandlerInterface $multiResponseHandler;

	protected function setUp():void{
		$this->initFactories();

		$options = new HTTPOptions([
			'ca_info' => __DIR__.'/cacert.pem',
			'sleep'   => 1,
		]);

		$this->multiResponseHandler = $this->getTestResponseHandler();

		$this->http = new CurlMultiClient($this->multiResponseHandler, $this->responseFactory, $options);
	}

	protected function getRequests():array{

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

	protected function getTestResponseHandler():\chillerlan\HTTP\MultiResponseHandlerInterface{

		return new class () implements \chillerlan\HTTP\MultiResponseHandlerInterface{

			protected array $responses = [];

			public function handleResponse(ResponseInterface $response, RequestInterface $request, int $id, array $curl_info):?RequestInterface{

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

	/**
	 * @todo
	 */
	public function testMultiRequest():void{

		/** @noinspection PhpUndefinedConstantInspection */
		if(defined('TEST_IS_CI') && TEST_IS_CI === true){
			$this->markTestSkipped('i have no idea why the headers are empty on travis');
		}

		$requests = $this->getRequests();

		$this->http
			->addRequests($requests)
			->process()
		;

		$responses = $this->multiResponseHandler->getResponses();

		$this::assertCount(10, $requests);
		$this::assertCount(10, $responses);

		try{
			// the responses are in the same order as the respective requests
			$this::assertSame(['de', 'en', 'es', 'fr', 'zh', 'de', 'en', 'es', 'fr', 'zh'], array_column($responses, 'lang'));
		}
		catch(ExpectationFailedException){
			$this::markTestSkipped('arenanet API error');
		}

		// cover the destructor
		unset($this->http);
	}

	public function testEmptyStackException():void{
		$this->expectException(ClientExceptionInterface::class);
		$this->expectExceptionMessage('request stack is empty');

		$this->http->process();
	}

}
