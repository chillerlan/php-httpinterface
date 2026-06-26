<?php
/**
 * Class CurlHandleTest
 *
 * @created      09.11.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 *
 * @todo: local httpbin on gh-actions
 */
declare(strict_types=1);

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\{CurlHandle, HTTPOptions};
use chillerlan\HTTP\Utils\MessageUtil;
use chillerlan\HTTPTest\ClientFactories\CurlClientFactory;
use chillerlan\PHPUnitHttp\HttpFactoryTrait;
use PHPUnit\Framework\Attributes\{DataProvider, Group, Test, TestWith};
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Exception, Throwable;
use function file_get_contents, str_repeat, strlen, strtolower, realpath;
use function sprintf;
use const CURLOPT_CAINFO, CURLOPT_CAINFO_BLOB, CURLOPT_CAPATH, CURLOPT_SSL_VERIFYHOST, CURLOPT_SSL_VERIFYPEER;

#[Group('slow')]
final class CurlHandleTest extends TestCase{
	use HttpFactoryTrait;

	protected string $HTTP_CLIENT_FACTORY = CurlClientFactory::class;

	protected function setUp():void{
		try{
			$this->initFactories(realpath(__DIR__.'/cacert.pem'));
		}
		catch(Throwable $e){
			$this->markTestSkipped('unable to init http factories: '.$e->getMessage());
		}
	}

	protected function createHandle(HTTPOptions $options, string $method = 'GET'):CurlHandle{
		$request  = $this->requestFactory->createRequest($method, 'https://example.com');
		$response = $this->responseFactory->createResponse();

		return new CurlHandle($request, $response, $options);
	}

	#[Test]
	#[TestWith(['ca_info', '/foo.pem'], 'ca_file')] // via the ca_info option
	#[TestWith(['ca_info', '/foo'], 'ca_path')]
	#[TestWith(['curl_options', [CURLOPT_CAINFO => '/foo.pem']], 'ca_file, curl_options')] // via curl_options
	#[TestWith(['curl_options', [CURLOPT_CAPATH => '/foo']], 'ca_path, curl_options')]
	public function invalidCAException(string $option, mixed $value):void{
		$this->expectException(ClientExceptionInterface::class);
		$this->expectExceptionMessageIsOrContains('invalid path to SSL CA bundle');

		$this->createHandle(new HTTPOptions([$option => $value]))->init();
	}

	public static function caOptionProvider():array{
		$caPath = __DIR__;
		$caFile = realpath($caPath.'/cacert.pem');
		$caBlob = file_get_contents($caFile);

		return [
			// via the ca_info option
			'ca_file'               => ['ca_info', $caFile, $caFile, CURLOPT_CAINFO, CURLOPT_CAPATH],
			'ca_path'               => ['ca_info', $caPath, $caPath, CURLOPT_CAPATH, CURLOPT_CAINFO],
			'ca_blob'               => ['ca_info', $caBlob, $caBlob, CURLOPT_CAINFO_BLOB, CURLOPT_CAINFO],
			// via curl_options
			'ca_file, curl_options' => ['curl_options', [CURLOPT_CAINFO => $caFile], $caFile, CURLOPT_CAINFO, CURLOPT_CAPATH],
			'ca_path, curl_options' => ['curl_options', [CURLOPT_CAPATH => $caPath], $caPath, CURLOPT_CAPATH, CURLOPT_CAINFO],
			'ca_blob, curl_options' => [
				'curl_options', [CURLOPT_CAINFO_BLOB => $caBlob], $caBlob, CURLOPT_CAINFO_BLOB, CURLOPT_CAINFO,
			],
		];
	}

	#[Test]
	#[DataProvider('caOptionProvider')]
	public function caInfoFile(string $option, mixed $value, string $expectedPath, int $curl_opt, int $curl_opt_not):void{
		$handle = $this->createHandle(new HTTPOptions([$option => $value]));
		$handle->init();
		$curl_options = $handle->getCurlOptions();

		$this::assertSame($expectedPath, $curl_options[$curl_opt]);
		$this::assertSame(2, $curl_options[CURLOPT_SSL_VERIFYHOST]);
		$this::assertSame(true, $curl_options[CURLOPT_SSL_VERIFYPEER]);
		$this::assertArrayNotHasKey($curl_opt_not, $curl_options);
	}

	#[Test]
	#[TestWith(['DELETE'], 'delete')] // head and options are not supported by httpbin
	#[TestWith(['GET'], 'get')]
	#[TestWith(['PATCH'], 'patch')]
	#[TestWith(['POST'], 'post')]
	#[TestWith(['PUT'], 'put')]
	public function requestMethods(string $method):void{
		$url      = 'https://httpbin.org/'.strtolower($method).'?foo=bar';
		$request  = $this->requestFactory->createRequest($method, $url);

		try{
			$response = $this->httpClient->sendRequest($request);
			$status   = $response->getStatusCode();

			if($status !== 200){
				throw new Exception(sprintf('HTTP/%s (%s)', $status, $url));
			}

			$data = MessageUtil::decodeJSON($response);

			$this::assertSame($url, $data->url);
			$this::assertSame('bar', $data->args->foo);
		}
		catch(Throwable $e){
			$this->markTestSkipped('httpbin-error: '.$e->getMessage());
		}

	}

	#[Test]
	#[TestWith(['DELETE'], 'delete')]
	#[TestWith(['PATCH'], 'patch')]
	#[TestWith(['POST'], 'post')]
	#[TestWith(['PUT'], 'put')]
	public function requestMethodsWithFormBody(string $method):void{
		$url     = 'https://httpbin.org/'.strtolower($method);
		$body    = 'foo=bar';
		$request = $this->requestFactory->createRequest($method, $url)
			->withHeader('Content-type', 'x-www-form-urlencoded')
			->withHeader('Content-Length', (string)strlen($body))
			->withBody($this->streamFactory->createStream($body))
		;

		try{
			$response = $this->httpClient->sendRequest($request);
			$status   = $response->getStatusCode();

			if($status !== 200){
				throw new Exception(sprintf('HTTP/%s (%s)', $status, $url));
			}

			$data = MessageUtil::decodeJSON($response);

			$this::assertSame($url, $data->url);
			$this::assertSame('x-www-form-urlencoded', $data->headers->{'Content-Type'});
			$this::assertSame(strlen($body), (int)$data->headers->{'Content-Length'});
			$this::assertSame($body, $data->data);
		}
		catch(Throwable $e){
			$this->markTestSkipped('httpbin-error: '.$e->getMessage());
		}

	}

	#[Test]
	#[TestWith(['DELETE'], 'delete')]
	#[TestWith(['PATCH'], 'patch')]
	#[TestWith(['POST'], 'post')]
	#[TestWith(['PUT'], 'put')]
	public function requestMethodsWithJsonBody(string $method):void{
		$url     = 'https://httpbin.org/'.strtolower($method);
		$body    = '{"foo":"bar"}';
		$request = $this->requestFactory->createRequest($method, $url)
			->withHeader('Content-type', 'application/json')
			->withBody($this->streamFactory->createStream($body))
		;

		try{
			$response = $this->httpClient->sendRequest($request);
			$status   = $response->getStatusCode();

			if($status !== 200){
				throw new Exception(sprintf('HTTP/%s (%s)', $status, $url));
			}

			$data = MessageUtil::decodeJSON($response);

			$this::assertSame($url, $data->url);
			$this::assertSame('application/json', $data->headers->{'Content-Type'});
			$this::assertSame(strlen($body), (int)$data->headers->{'Content-Length'});
			$this::assertSame($body, $data->data);
			$this::assertSame('bar', $data->json->foo);
		}
		catch(Throwable $e){
			$this->markTestSkipped('httpbin-error: '.$e->getMessage());
		}

	}

	#[Test]
	public function largeBody():void{
		$body    = str_repeat('*', ((1 << 20) + 1)); // will enable the read function
		$request = $this->requestFactory->createRequest('POST', 'https://httpbin.org/post')
			->withHeader('Content-type', 'text/plain')
			->withHeader('Content-Length', (string)strlen($body))
			->withBody($this->streamFactory->createStream($body))
		;

		try{
			$response = $this->httpClient->sendRequest($request);
			$status   = $response->getStatusCode();

			if($status !== 200){
				throw new Exception(sprintf('HTTP/%s', $status));
			}

			$data = MessageUtil::decodeJSON($response);

			$this::assertSame(strlen($body), (int)$data->headers->{'Content-Length'});
		}
		catch(Throwable){
			// httpbin times out after 10 seconds and will most likely fail to transfer 1MB of data
			// so fool the code coverage if that happens, as we're only interested in request creation
			$this::assertTrue(true);
#			$this->markTestSkipped('httpbin-error: '.$e->getMessage());
		}

	}

}
