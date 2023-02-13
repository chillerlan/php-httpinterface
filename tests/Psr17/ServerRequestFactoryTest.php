<?php
/**
 * @author       http-factory-tests Contributors
 * @license      MIT
 * @link         https://github.com/http-interop/http-factory-tests
 *
 * @noinspection PhpUndefinedConstantInspection
 */

namespace chillerlan\HTTPTest\Psr17;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use function class_exists;
use function defined;
use function microtime;
use function sprintf;
use const UPLOAD_ERR_OK;

class ServerRequestFactoryTest extends TestCase{

	protected ServerRequestFactoryInterface $serverRequestFactory;
	protected UriFactoryInterface           $uriFactory;

	public function setUp():void{

		if(!defined('SERVER_REQUEST_FACTORY') || !class_exists(SERVER_REQUEST_FACTORY)){
			$this::markTestSkipped('SERVER_REQUEST_FACTORY class name not provided');
		}

		if(!defined('URI_FACTORY') || !class_exists(URI_FACTORY)){
			$this::markTestSkipped('URI_FACTORY class name not provided');
		}

		// phpunit 10 "fix"
		$_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);

		$this->serverRequestFactory = new (SERVER_REQUEST_FACTORY);
		$this->uriFactory           = new (URI_FACTORY);
	}

	public static function dataServer():array{
		$methods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'HEAD'];
		$data    = [];

		foreach($methods as $method){
			$data[$method] = [
				[
					'REQUEST_METHOD' => $method,
					'REQUEST_URI'    => '/test',
					'QUERY_STRING'   => 'foo=1&bar=true',
					'HTTP_HOST'      => 'example.org',
				],
			];
		}

		return $data;
	}

	/**
	 * @dataProvider dataServer
	 */
	public function testCreateServerRequest(array $server):void{
		$method  = $server['REQUEST_METHOD'];
		$uri     = "http://{$server['HTTP_HOST']}{$server['REQUEST_URI']}?{$server['QUERY_STRING']}";
		$request = $this->serverRequestFactory->createServerRequest($method, $uri);

		$this::assertInstanceOf(ServerRequestInterface::class, $request);
		$this::assertSame($method, $request->getMethod());
		$this::assertSame($uri, (string)$request->getUri());
	}

	/**
	 * @dataProvider dataServer
	 */
	public function testCreateServerRequestFromArray(array $server):void{
		$method  = $server['REQUEST_METHOD'];
		$uri     = sprintf('http://%s%s?%s', $server['HTTP_HOST'], $server['REQUEST_URI'], $server['QUERY_STRING']);
		$request = $this->serverRequestFactory->createServerRequest($method, $uri, $server);

		$this::assertSame($method, $request->getMethod());
		$this::assertSame($uri, (string)$request->getUri());
	}

	/**
	 * @dataProvider dataServer
	 */
	public function testCreateServerRequestWithUriObject(array $server):void{
		$method  = $server['REQUEST_METHOD'];
		$uri     = sprintf('http://%s%s?%s', $server['HTTP_HOST'], $server['REQUEST_URI'], $server['QUERY_STRING']);
		$request = $this->serverRequestFactory->createServerRequest($method, $this->uriFactory->createUri($uri));

		$this::assertSame($method, $request->getMethod());
		$this::assertSame($uri, (string)$request->getUri());
	}

	/**
	 * @backupGlobals enabled
	 */
	public function testCreateServerRequestDoesNotReadServerSuperglobal():void{
		$_SERVER = ['HTTP_X_FOO' => 'bar'];

		$server = [
			'REQUEST_METHOD' => 'PUT',
			'REQUEST_URI'    => '/test',
			'QUERY_STRING'   => 'super=0',
			'HTTP_HOST'      => 'example.org',
		];

		$request      = $this->serverRequestFactory->createServerRequest('PUT', '/test', $server);
		$serverParams = $request->getServerParams();

		$this::assertNotEquals($_SERVER, $serverParams);
		$this::assertArrayNotHasKey('HTTP_X_FOO', $serverParams);
	}

	public function testCreateServerRequestDoesNotReadCookieSuperglobal():void{
		$_COOKIE = ['foo' => 'bar'];

		$request = $this->serverRequestFactory->createServerRequest('POST', 'http://example.org/test');

		$this::assertEmpty($request->getCookieParams());
	}

	public function testCreateServerRequestDoesNotReadGetSuperglobal():void{
		$_GET = ['foo' => 'bar'];

		$request = $this->serverRequestFactory->createServerRequest('POST', 'http://example.org/test');

		$this::assertEmpty($request->getQueryParams());
	}

	public function testCreateServerRequestDoesNotReadFilesSuperglobal():void{
		$_FILES = [
			[
				'name'     => 'foobar.dat',
				'type'     => 'application/octet-stream',
				'tmp_name' => '/tmp/php45sd3f',
				'error'    => UPLOAD_ERR_OK,
				'size'     => 4,
			],
		];

		$request = $this->serverRequestFactory->createServerRequest('POST', 'http://example.org/test');

		$this::assertEmpty($request->getUploadedFiles());
	}

	public function testCreateServerRequestDoesNotReadPostSuperglobal():void{
		$_POST = ['foo' => 'bar'];

		$request = $this->serverRequestFactory->createServerRequest('POST', 'http://example.org/test');

		$this::assertEmpty($request->getParsedBody());
	}

}
