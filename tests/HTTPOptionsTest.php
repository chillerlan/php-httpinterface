<?php
/**
 * Class HTTPOptionsTest
 *
 * @filesource   HTTPOptionsTest.php
 * @created      14.11.2018
 * @package      chillerlan\HTTPTest
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\HTTPOptions;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;

class HTTPOptionsTest extends TestCase{

	public function testConvertInvalidCurlOptionsValueToArray(){
		$this->assertTrue(is_array((new HTTPOptions(['curl_options' => 'foo']))->curl_options)); //coverage
	}

	public function testInvalidUserAgentException(){
		$this->expectException(ClientExceptionInterface::class);
		$this->expectExceptionMessage('invalid user agent');

		new HTTPOptions(['user_agent' => false]);
	}

	public function testCaDisable(){
		$o = new HTTPOptions([
			'ssl_verifypeer' => false,
			'curl_options'   => [
				CURLOPT_CAINFO => 'foo',
				CURLOPT_CAPATH => 'bar',
			],
		]);

		$this->assertSame(0, $o->curl_options[CURLOPT_SSL_VERIFYHOST]);
		$this->assertSame(false, $o->curl_options[CURLOPT_SSL_VERIFYPEER]);
		$this->assertArrayNotHasKey(CURLOPT_CAINFO, $o->curl_options);
		$this->assertArrayNotHasKey(CURLOPT_CAPATH, $o->curl_options);
	}

	public function testCaInfoFile(){
		$file = __DIR__.'/cacert.pem';
		$o = new HTTPOptions(['ca_info' => $file]);

		$this->assertSame($file, $o->curl_options[CURLOPT_CAINFO]);
		$this->assertSame(2, $o->curl_options[CURLOPT_SSL_VERIFYHOST]);
		$this->assertSame(true, $o->curl_options[CURLOPT_SSL_VERIFYPEER]);
		$this->assertArrayNotHasKey(CURLOPT_CAPATH, $o->curl_options);
	}

	public function testCaInfoDir(){
		$dir = __DIR__;
		$o = new HTTPOptions(['ca_info' => $dir]);

		$this->assertSame($dir, $o->curl_options[CURLOPT_CAPATH]);
		$this->assertSame(2, $o->curl_options[CURLOPT_SSL_VERIFYHOST]);
		$this->assertSame(true, $o->curl_options[CURLOPT_SSL_VERIFYPEER]);
		$this->assertArrayNotHasKey(CURLOPT_CAINFO, $o->curl_options);
	}

	public function testCaInfoInvalidException(){
		$this->expectException(ClientExceptionInterface::class);
		$this->expectExceptionMessage('invalid path to SSL CA bundle (HTTPOptions::$ca_info): foo');

		new HTTPOptions(['ca_info' => 'foo']);
	}

	public function testCurloptCaInfoFile(){
		$file = __DIR__.'/cacert.pem';
		$o = new HTTPOptions(['curl_options' => [CURLOPT_CAINFO => $file]]);

		$this->assertSame($file, $o->curl_options[CURLOPT_CAINFO]);
		$this->assertSame(2, $o->curl_options[CURLOPT_SSL_VERIFYHOST]);
		$this->assertSame(true, $o->curl_options[CURLOPT_SSL_VERIFYPEER]);
		$this->assertArrayNotHasKey(CURLOPT_CAPATH, $o->curl_options);
	}

	public function testCurloptCaInfoDir(){
		$dir = __DIR__;
		$o = new HTTPOptions(['curl_options' => [CURLOPT_CAPATH => $dir]]);

		$this->assertSame($dir, $o->curl_options[CURLOPT_CAPATH]);
		$this->assertSame(2, $o->curl_options[CURLOPT_SSL_VERIFYHOST]);
		$this->assertSame(true, $o->curl_options[CURLOPT_SSL_VERIFYPEER]);
		$this->assertArrayNotHasKey(CURLOPT_CAINFO, $o->curl_options);
	}

	public function testCurloptCaInfoInvalidException(){
		$this->expectException(ClientExceptionInterface::class);
		$this->expectExceptionMessage('invalid path to SSL CA bundle (CURLOPT_CAPATH/CURLOPT_CAINFO): foo');

		new HTTPOptions(['curl_options' => [CURLOPT_CAINFO => 'foo']]);
	}

	public function testCaInfoFallback(){

		if(file_exists(ini_get('curl.cainfo'))){
			$this->markTestSkipped('curl.cainfo set');
		}

		$o = new HTTPOptions;

		$this->assertFileExists($o->curl_options[CURLOPT_CAINFO]);
		$this->assertSame(2, $o->curl_options[CURLOPT_SSL_VERIFYHOST]);
		$this->assertSame(true, $o->curl_options[CURLOPT_SSL_VERIFYPEER]);
	}

}
