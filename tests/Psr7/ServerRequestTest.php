<?php
/**
 * Class ServerRequestTest
 *
 * @link https://github.com/guzzle/psr7/blob/4b981cdeb8c13d22a6c193554f8c686f53d5c958/tests/ServerRequestTest.php
 *
 * @filesource   ServerRequestTest.php
 * @created      12.08.2018
 * @package      chillerlan\HTTPTest\Psr7
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\{ServerRequest, UploadedFile};
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ServerRequestTest extends TestCase{

	public function testServerParams(){
		$params = ['name' => 'value'];

		$r = new ServerRequest(ServerRequest::METHOD_GET, '/', [], null, '1.1', $params);
		$this->assertSame($params, $r->getServerParams());
	}

	public function testCookieParams(){
		$r1 = new ServerRequest('GET', '/');

		$params = ['name' => 'value'];

		$r2 = $r1->withCookieParams($params);

		$this->assertNotSame($r2, $r1);
		$this->assertEmpty($r1->getCookieParams());
		$this->assertSame($params, $r2->getCookieParams());
	}

	public function testQueryParams(){
		$r1 = new ServerRequest('GET', '/');

		$params = ['name' => 'value'];

		$r2 = $r1->withQueryParams($params);

		$this->assertNotSame($r2, $r1);
		$this->assertEmpty($r1->getQueryParams());
		$this->assertSame($params, $r2->getQueryParams());
	}

	public function testParsedBody(){
		$r1 = new ServerRequest('GET', '/');

		$params = ['name' => 'value'];

		$r2 = $r1->withParsedBody($params);

		$this->assertNotSame($r2, $r1);
		$this->assertEmpty($r1->getParsedBody());
		$this->assertSame($params, $r2->getParsedBody());
	}

	public function testParsedBodyInvalidArg(){
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('parsed body value must be an array, object or null');

		(new ServerRequest('GET', '/'))->withParsedBody('');
	}

	public function testAttributes(){
		$r1 = new ServerRequest('GET', '/');

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
		$r = (new ServerRequest('GET', '/'))->withAttribute('name', null);

		$this->assertSame(['name' => null], $r->getAttributes());
		$this->assertNull($r->getAttribute('name', 'different-default'));

		$requestWithoutAttribute = $r->withoutAttribute('name');

		$this->assertSame([], $requestWithoutAttribute->getAttributes());
		$this->assertSame('different-default', $requestWithoutAttribute->getAttribute('name', 'different-default'));
	}

	public function testUploadedFiles(){
		$r1 = new ServerRequest('GET', '/');

		$files = [
			'file' => new UploadedFile('test', 123, UPLOAD_ERR_OK)
		];

		$r2 = $r1->withUploadedFiles($files);

		$this->assertNotSame($r2, $r1);
		$this->assertSame([], $r1->getUploadedFiles());
		$this->assertSame($files, $r2->getUploadedFiles());
	}

}
