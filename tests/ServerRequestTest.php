<?php
/**
 * Class ServerRequestTest
 *
 * @link https://github.com/guzzle/psr7/blob/4b981cdeb8c13d22a6c193554f8c686f53d5c958/tests/ServerRequestTest.php
 *
 * @filesource   ServerRequestTest.php
 * @created      12.08.2018
 * @package      chillerlan\HTTPTest
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\{ServerRequest, UploadedFile, Uri};

class ServerRequestTest extends HTTPTestAbstract{

	public function testServerParams(){
		$params = ['name' => 'value'];

		$r = new ServerRequest(ServerRequest::METHOD_GET, '/', [], null, '1.1', $params);
		$this->assertSame($params, $r->getServerParams());
	}

	public function testCookieParams(){
		$r1 = $this->factory->createServerRequest($this->factory::METHOD_GET, '/');

		$params = ['name' => 'value'];

		$r2 = $r1->withCookieParams($params);

		$this->assertNotSame($r2, $r1);
		$this->assertEmpty($r1->getCookieParams());
		$this->assertSame($params, $r2->getCookieParams());
	}

	public function testQueryParams(){
		$r1 = $this->factory->createServerRequest($this->factory::METHOD_GET, '/');

		$params = ['name' => 'value'];

		$r2 = $r1->withQueryParams($params);

		$this->assertNotSame($r2, $r1);
		$this->assertEmpty($r1->getQueryParams());
		$this->assertSame($params, $r2->getQueryParams());
	}

	public function testParsedBody(){
		$r1 = $this->factory->createServerRequest($this->factory::METHOD_GET, '/');

		$params = ['name' => 'value'];

		$r2 = $r1->withParsedBody($params);

		$this->assertNotSame($r2, $r1);
		$this->assertEmpty($r1->getParsedBody());
		$this->assertSame($params, $r2->getParsedBody());
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage parsed body value must be an array, object or null
	 */
	public function testParsedBodyInvalidArg(){
		$this->factory->createServerRequest($this->factory::METHOD_GET, '/')->withParsedBody('');
	}

	public function testAttributes(){
		$r1 = $this->factory->createServerRequest($this->factory::METHOD_GET, '/');

		$r2 = $r1->withAttribute('name', 'value');
		$r3 = $r2->withAttribute('other', 'otherValue');
		$r4 = $r3->withoutAttribute('other');
		$r5 = $r3->withoutAttribute('unknown');

		$this->assertNotSame($r2, $r1);
		$this->assertNotSame($r3, $r2);
		$this->assertNotSame($r4, $r3);
		$this->assertSame($r5, $r3);

		$this->assertSame([], $r1->getAttributes());
		$this->assertNull($r1->getAttribute('name'));
		$this->assertSame('something', $r1->getAttribute('name', 'something'), 'Should return the default value');

		$this->assertSame('value', $r2->getAttribute('name'));
		$this->assertSame(['name' => 'value'], $r2->getAttributes());
		$this->assertEquals(['name' => 'value', 'other' => 'otherValue'], $r3->getAttributes());
		$this->assertSame(['name' => 'value'], $r4->getAttributes());
	}

	public function testNullAttribute(){
		$r = $this->factory->createServerRequest($this->factory::METHOD_GET, '/')->withAttribute('name', null);

		$this->assertSame(['name' => null], $r->getAttributes());
		$this->assertNull($r->getAttribute('name', 'different-default'));

		$requestWithoutAttribute = $r->withoutAttribute('name');

		$this->assertSame([], $requestWithoutAttribute->getAttributes());
		$this->assertSame('different-default', $requestWithoutAttribute->getAttribute('name', 'different-default'));
	}

	public function testUploadedFiles(){
		$r1 = $this->factory->createServerRequest($this->factory::METHOD_GET, '/');

		$files = [
			'file' => new UploadedFile('test', 123, UPLOAD_ERR_OK)
		];

		$r2 = $r1->withUploadedFiles($files);

		$this->assertNotSame($r2, $r1);
		$this->assertSame([], $r1->getUploadedFiles());
		$this->assertSame($files, $r2->getUploadedFiles());
	}

	public function testFromGlobals(){

		$_SERVER = [
			'REQUEST_URI' => '/blog/article.php?id=10&user=foo',
			'SERVER_PORT' => '443',
			'SERVER_ADDR' => '217.112.82.20',
			'SERVER_NAME' => 'www.example.org',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'REQUEST_METHOD' => 'POST',
			'QUERY_STRING' => 'id=10&user=foo',
			'DOCUMENT_ROOT' => '/path/to/your/server/root/',
			'HTTP_HOST' => 'www.example.org',
			'HTTPS' => 'on',
			'REMOTE_ADDR' => '193.60.168.69',
			'REMOTE_PORT' => '5390',
			'SCRIPT_NAME' => '/blog/article.php',
			'SCRIPT_FILENAME' => '/path/to/your/server/root/blog/article.php',
			'PHP_SELF' => '/blog/article.php',
		];

		$_COOKIE = [
			'logged-in' => 'yes!'
		];

		$_POST = [
			'name' => 'Pesho',
			'email' => 'pesho@example.com',
		];

		$_GET = [
			'id' => 10,
			'user' => 'foo',
		];

		$_FILES = [
			'file' => [
				'name' => 'MyFile.txt',
				'type' => 'text/plain',
				'tmp_name' => '/tmp/php/php1h4j1o',
				'error' => UPLOAD_ERR_OK,
				'size' => 123,
			]
		];

		$server = $this->factory->createServerRequestFromGlobals();

		$this->assertSame('POST', $server->getMethod());
		$this->assertEquals(['Host' => ['www.example.org']], $server->getHeaders());
		$this->assertSame('', (string) $server->getBody());
		$this->assertSame('1.1', $server->getProtocolVersion());
		$this->assertEquals($_COOKIE, $server->getCookieParams());
		$this->assertEquals($_POST, $server->getParsedBody());
		$this->assertEquals($_GET, $server->getQueryParams());

		$this->assertEquals(
			$this->factory->createUri('https://www.example.org/blog/article.php?id=10&user=foo'),
			$server->getUri()
		);

		$expectedFiles = [
			'file' => new UploadedFile(
				'/tmp/php/php1h4j1o',
				123,
				UPLOAD_ERR_OK,
				'MyFile.txt',
				'text/plain'
			),
		];

		$this->assertEquals($expectedFiles, $server->getUploadedFiles());
	}

}
