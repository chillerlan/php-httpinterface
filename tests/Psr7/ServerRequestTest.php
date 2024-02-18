<?php
/**
 * Class ServerRequestTest
 *
 * @link https://github.com/guzzle/psr7/blob/4b981cdeb8c13d22a6c193554f8c686f53d5c958/tests/ServerRequestTest.php
 *
 * @created      12.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\{ServerRequest, UploadedFile};
use Fig\Http\Message\RequestMethodInterface;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use const UPLOAD_ERR_OK;

/**
 *
 */
class ServerRequestTest extends TestCase{

	public function testServerParams():void{
		$params = ['name' => 'value'];

		$request = new ServerRequest(RequestMethodInterface::METHOD_GET, '/', $params);
		$this::assertSame($params, $request->getServerParams());
	}

	public function testCookieParams():void{
		$request = new ServerRequest('GET', '/');

		$this::assertEmpty($request->getCookieParams());

		$params = ['name' => 'value'];

		$request->withCookieParams($params);

		$this::assertSame($params, $request->getCookieParams());
	}

	public function testQueryParams():void{
		$request = new ServerRequest('GET', '/');

		$this::assertEmpty($request->getQueryParams());

		$params = ['name' => 'value'];

		$request->withQueryParams($params);

		$this::assertSame($params, $request->getQueryParams());
	}

	public function testParsedBody():void{
		$request = new ServerRequest('GET', '/');

		$this::assertEmpty($request->getParsedBody());

		$params = ['name' => 'value'];

		$request->withParsedBody($params);

		$this::assertSame($params, $request->getParsedBody());
	}

	public function testParsedBodyInvalidArg():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('parsed body value must be an array, object or null');

		/** @noinspection PhpParamsInspection */
		(new ServerRequest('GET', '/'))->withParsedBody('');
	}

	public function testAttributes():void{
		$request = new ServerRequest('GET', '/');

		$this::assertSame([], $request->getAttributes());
		$this::assertNull($request->getAttribute('name'));
		$this::assertSame('something', $request->getAttribute('name', 'something'), 'Should return the default value');

		$request->withAttribute('name', 'value');

		$this::assertSame('value', $request->getAttribute('name'));
		$this::assertSame(['name' => 'value'], $request->getAttributes());

		$request->withAttribute('other', 'otherValue');

		$this::assertSame(['name' => 'value', 'other' => 'otherValue'], $request->getAttributes());

		$request->withoutAttribute('other');

		$this::assertSame(['name' => 'value'], $request->getAttributes());
	}

	public function testNullAttribute():void{
		$request = (new ServerRequest('GET', '/'))->withAttribute('name', null);

		$this::assertSame(['name' => null], $request->getAttributes());
		$this::assertNull($request->getAttribute('name', 'different-default'));

		$request->withoutAttribute('name');

		$this::assertSame([], $request->getAttributes());
		$this::assertSame('different-default', $request->getAttribute('name', 'different-default'));
	}

	public function testUploadedFiles():void{
		$request = new ServerRequest('GET', '/');

		$this::assertSame([], $request->getUploadedFiles());

		$files = ['file' => new UploadedFile('test', 123, UPLOAD_ERR_OK)];

		$request->withUploadedFiles($files);

		$this::assertSame($files, $request->getUploadedFiles());
	}

}
