<?php
/**
 * Class HTTPClientTestAbstract
 *
 * @filesource   HTTPClientTestAbstract.php
 * @created      10.11.2018
 * @package      chillerlan\HTTPTest\Psr18
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr18;

use chillerlan\HTTP\Psr7\Request;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;

use function chillerlan\HTTP\Psr7\get_json;
use function in_array;

abstract class HTTPClientTestAbstract extends TestCase{

	protected const USER_AGENT = 'chillerlanHttpTest/2.0';

	/**
	 * @var \Psr\Http\Client\ClientInterface
	 */
	protected $http;

	public function testSendRequest(){

		try{
			$url      = 'https://httpbin.org/get';
			$response = $this->http->sendRequest(new Request(Request::METHOD_GET, $url));
			$json     = get_json($response);

			$this->assertSame($url, $json->url);
			$this->assertSame($this::USER_AGENT, $json->headers->{'User-Agent'});
			$this->assertSame(200, $response->getStatusCode());
			$this->assertSame(200, $response->getStatusCode());
		}
		catch(Exception $e){
			$this->markTestSkipped('error: '.$e->getMessage());
		}

	}

	public function testNetworkError(){
		$this->expectException(ClientExceptionInterface::class);

		$this->http->sendRequest(new Request(Request::METHOD_GET, 'http://foo'));
	}

}
