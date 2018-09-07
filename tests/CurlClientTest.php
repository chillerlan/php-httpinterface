<?php
/**
 * Class HTTPClientTest
 *
 * @filesource   HTTPClientTest.php
 * @created      28.08.2018
 * @package      chillerlan\HTTPTest
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\{CurlClient, HTTPOptions, Psr7\Request};
use PHPUnit\Framework\TestCase;

class CurlClientTest extends TestCase{

	protected const USER_AGENT = 'chillerlanHttpTest/2.0';

	/**
	 * @var \chillerlan\HTTP\HTTPClientInterface
	 */
	protected $http;

	protected function setUp(){
		$options = new HTTPOptions([
			'ca_info' => __DIR__.'/cacert.pem',
			'user_agent' => $this::USER_AGENT,
		]);

		$this->http = new CurlClient($options);
	}

	public function testSendRequest(){

		try{
			$url      = 'https://httpbin.org/get';
			$response = $this->http->sendRequest(new Request(Request::METHOD_GET, $url));
			$json     = json_decode($response->getBody()->getContents());

			$this->assertSame($url, $json->url);
			$this->assertSame($this::USER_AGENT, $json->headers->{'User-Agent'});
			$this->assertSame(200, $response->getStatusCode());
			$this->assertSame(200, $response->getStatusCode());
		}
		catch(\Exception $e){
			$this->markTestSkipped('httpbin.org error: '.$e->getMessage());
		}

	}

	public function requestDataProvider():array {
		return [
			'get'        => ['get',    []],
			'post'       => ['post',   []],
			'post-json'  => ['post',   ['Content-type' => 'application/json']],
			'post-form'  => ['post',   ['Content-type' => 'application/x-www-form-urlencoded']],
			'put-json'   => ['put',    ['Content-type' => 'application/json']],
			'put-form'   => ['put',    ['Content-type' => 'application/x-www-form-urlencoded']],
			'patch-json' => ['patch',  ['Content-type' => 'application/json']],
			'patch-form' => ['patch',  ['Content-type' => 'application/x-www-form-urlencoded']],
			'delete'     => ['delete', []],
		];
	}

	/**
	 * @dataProvider requestDataProvider
	 *
	 * @param $method
	 * @param $extra_headers
	 */
	public function testRequest(string $method, array $extra_headers){

		try{
			$response = $this->http->request(
				'https://httpbin.org/'.$method,
				$method,
				['foo' => 'bar'],
				['huh' => 'wtf'],
				['what' => 'nope'] + $extra_headers
			);

		}
		catch(\Exception $e){
			$this->markTestSkipped('httpbin.org error: '.$e->getMessage());
		}

		$json = json_decode($response->getBody()->getContents());

		if(!$json){
			$this->markTestSkipped('empty response');
		}
		else{
			$this->assertSame('https://httpbin.org/'.$method.'?foo=bar', $json->url);
			$this->assertSame('bar', $json->args->foo);
			$this->assertSame('nope', $json->headers->What);
			$this->assertSame(self::USER_AGENT, $json->headers->{'User-Agent'});

			if(in_array($method, ['patch', 'post', 'put'])){

				if(isset($extra_headers['content-type']) && $extra_headers['content-type'] === 'application/json'){
					$this->assertSame('wtf', $json->json->huh);
				}
				else{
					$this->assertSame('wtf', $json->form->huh);
				}

			}
		}

	}


}
