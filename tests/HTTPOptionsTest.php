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

use chillerlan\HTTP\CurlHandle;
use chillerlan\HTTP\HTTPOptions;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
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

	public function testInvalidCAException():void{
		$this->expectException(ClientExceptionInterface::class);
		$this->expectExceptionMessage('invalid path to SSL CA bundle');

		new HTTPOptions(['ca_info' => 'foo']);
	}

	public function testCaInfoFile():void{
		$file         = __DIR__.'/cacert.pem';
		// via the ca_info option
		$options      = new HTTPOptions(['ca_info' => $file]);
		$curl_options = $this->createTestHandleOptions($options);
		$this::assertSame($file, $curl_options[CURLOPT_CAINFO]);
		$this::assertSame(2, $curl_options[CURLOPT_SSL_VERIFYHOST]);
		$this::assertSame(true, $curl_options[CURLOPT_SSL_VERIFYPEER]);
		$this::assertArrayNotHasKey(CURLOPT_CAPATH, $curl_options);

		// via curl_options
		$options      = new HTTPOptions(['curl_options' => [CURLOPT_CAINFO => $file]]);
		$curl_options = $this->createTestHandleOptions($options);
		$this::assertSame($file, $curl_options[CURLOPT_CAINFO]);
		$this::assertSame(2, $curl_options[CURLOPT_SSL_VERIFYHOST]);
		$this::assertSame(true, $curl_options[CURLOPT_SSL_VERIFYPEER]);
		$this::assertArrayNotHasKey(CURLOPT_CAPATH, $curl_options);
	}

	public function testCaInfoDir():void{
		$dir          = __DIR__;
		// via the ca_info option
		$options      = new HTTPOptions(['ca_info' => $dir]);
		$curl_options = $this->createTestHandleOptions($options);
		$this::assertSame($dir, $curl_options[CURLOPT_CAPATH]);
		$this::assertSame(2, $curl_options[CURLOPT_SSL_VERIFYHOST]);
		$this::assertSame(true, $curl_options[CURLOPT_SSL_VERIFYPEER]);
		$this::assertArrayNotHasKey(CURLOPT_CAINFO, $curl_options);

		// via curl_options
		$options      = new HTTPOptions(['curl_options' => [CURLOPT_CAPATH => $dir]]);
		$curl_options = $this->createTestHandleOptions($options);
		$this::assertSame($dir, $curl_options[CURLOPT_CAPATH]);
		$this::assertSame(2, $curl_options[CURLOPT_SSL_VERIFYHOST]);
		$this::assertSame(true, $curl_options[CURLOPT_SSL_VERIFYPEER]);
		$this::assertArrayNotHasKey(CURLOPT_CAINFO, $curl_options);
	}

	public function testCaInfoInvalidException():void{
		$this->expectException(ClientExceptionInterface::class);
		$this->expectExceptionMessage('invalid path to SSL CA bundle');

		new HTTPOptions(['ca_info' => 'foo']);
	}

	public function testCurloptCaInfoInvalidException():void{
		$this->expectException(ClientExceptionInterface::class);
		$this->expectExceptionMessage('invalid path to SSL CA bundle');

		new HTTPOptions(['curl_options' => [CURLOPT_CAINFO => 'foo']]);
	}
/*
	public function testCaInfoFallback():void{

		if(file_exists(ini_get('curl.cainfo'))){
			$this->markTestSkipped('curl.cainfo set');
		}

		$options      = new HTTPOptions;
		$curl_options = $this->createTestHandleOptions($options);

		$this::assertFileExists($curl_options[CURLOPT_CAINFO]);
		$this::assertSame(2, $curl_options[CURLOPT_SSL_VERIFYHOST]);
		$this::assertSame(true, $curl_options[CURLOPT_SSL_VERIFYPEER]);
	}
*/
	public function testSetVerifyPeer():void{
		// no ca given -> false
		$options      = new HTTPOptions(['ssl_verifypeer' => true]);
		$curl_options = $this->createTestHandleOptions($options);
		$this::assertFalse($curl_options[CURLOPT_SSL_VERIFYPEER]);

		// with CA
		$options      = new HTTPOptions(['ssl_verifypeer' => true, 'ca_info' => __DIR__.'/cacert.pem']);
		$curl_options = $this->createTestHandleOptions($options);
		$this::assertTrue($curl_options[CURLOPT_SSL_VERIFYPEER]);

		// set to false, obv
		$options      = new HTTPOptions(['ssl_verifypeer' => false]);
		$curl_options = $this->createTestHandleOptions($options);
		$this::assertFalse($curl_options[CURLOPT_SSL_VERIFYPEER]);
	}

}
