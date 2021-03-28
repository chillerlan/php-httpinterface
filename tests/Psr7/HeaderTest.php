<?php
/**
 * Class HeaderTest
 *
 * @created      28.03.2021
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2021 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\{Header, Response};
use PHPUnit\Framework\TestCase;

/**
 *
 */
class HeaderTest extends TestCase{

	public function headerDataProvider():array{
		return [
			'content-Type'  => [['Content-Type' => 'application/x-www-form-urlencoded'], ['Content-Type' => 'application/x-www-form-urlencoded']],
			'lowercasekey'  => [['lowercasekey' => 'lowercasevalue'], ['Lowercasekey' => 'lowercasevalue']],
			'UPPERCASEKEY'  => [['UPPERCASEKEY' => 'UPPERCASEVALUE'], ['Uppercasekey' => 'UPPERCASEVALUE']],
			'mIxEdCaSeKey'  => [['mIxEdCaSeKey' => 'MiXeDcAsEvAlUe'], ['Mixedcasekey' => 'MiXeDcAsEvAlUe']],
			'31i71casekey'  => [['31i71casekey' => '31i71casevalue'], ['31i71casekey' => '31i71casevalue']],
			'numericvalue'  => [['numericvalue:1'], ['Numericvalue'  => '1']],
			'numericvalue2' => [['numericvalue' => 2], ['Numericvalue'  => '2']],
			'keyvaluearray' => [[['foo' => 'bar']], ['Foo' => 'bar']],
			'arrayvalue'    => [['foo' => ['bar', 'baz']], ['Foo' => 'bar, baz']],
			'invalid: 2'    => [[2 => 2], []],
			'invalid: what' => [['what'], []],
		];
	}

	/**
	 * @dataProvider headerDataProvider
	 *
	 * @param array $headers
	 * @param array $normalized
	 */
	public function testNormalizeHeaders(array $headers, array $normalized):void{
		$this::assertSame($normalized, Header::normalize($headers));
	}

	public function testCombineHeaderFields():void{

		$headers = [
			'accept:',
			'Accept: foo',
			'accept' => 'bar',
			'x-Whatever :nope',
			'X-whatever' => '',
			'x-foo' => 'bar',
			'x - fOO: baz ',
			' x-foo ' => ['what', 'nope'],
		];

		$this::assertSame([
			'Accept'     => 'foo, bar',
			'X-Whatever' => 'nope',
			'X-Foo'      => 'bar, baz, what, nope'
		], Header::normalize($headers));

		$r = new Response;

		foreach(Header::normalize($headers) as $k => $v){
			$r = $r->withAddedHeader($k, $v);
		}

		$this::assertSame( [
			'Accept'     => ['foo, bar'],
			'X-Whatever' => ['nope'],
			'X-Foo'      => ['bar, baz, what, nope']
		], $r->getHeaders());

	}

	public function testCombinedCookieHeaders():void{

		$headers = [
			'Set-Cookie: foo=bar',
			'Set-Cookie: foo=baz',
			'Set-Cookie: whatever=nope; HttpOnly',
		];

		$this::assertSame([
			'Set-Cookie' => [
				'foo'      => 'foo=baz',
				'whatever' => 'whatever=nope; HttpOnly'
			]
		], Header::normalize($headers));
	}


}
