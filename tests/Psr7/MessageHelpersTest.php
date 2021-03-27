<?php
/**
 * Class MessageHelpersTest
 *
 * @created      01.09.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\{Request, Response, Uri};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use TypeError;

use function chillerlan\HTTP\Psr17\create_stream;
use function chillerlan\HTTP\Psr7\{
	build_http_query, clean_query_params, decompress_content, get_json, get_xml,
	merge_query, message_to_string, normalize_message_headers, r_rawurlencode,
	uriIsAbsolute, uriIsAbsolutePathReference, uriIsNetworkPathReference,
	uriIsRelativePathReference, uriWithoutQueryValue, uriWithQueryValue
};

use const chillerlan\HTTP\Psr7\{BOOLEANS_AS_BOOL, BOOLEANS_AS_INT, BOOLEANS_AS_INT_STRING, BOOLEANS_AS_STRING};

class MessageHelpersTest extends TestCase{

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
		$this::assertSame($normalized, normalize_message_headers($headers));
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
		], normalize_message_headers($headers));

		$r = new Response;

		foreach(normalize_message_headers($headers) as $k => $v){
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
		], normalize_message_headers($headers));
	}

	public function queryParamDataProvider():array{
		return [
			// don't remove empty values
			'BOOLEANS_AS_BOOL (clean)' => [['whatever' => null, 'nope' => '', 'true' => true, 'false' => false, 'array' => ['value' => false]], BOOLEANS_AS_BOOL, false],
			// bool cast to types
			'BOOLEANS_AS_BOOL'         => [['true' => true, 'false' => false, 'array' => ['value' => false]], BOOLEANS_AS_BOOL, true],
			'BOOLEANS_AS_INT'          => [['true' => 1, 'false' => 0, 'array' => ['value' => 0]], BOOLEANS_AS_INT, true],
			'BOOLEANS_AS_INT_STRING'   => [['true' => '1', 'false' => '0', 'array' => ['value' => '0']], BOOLEANS_AS_INT_STRING, true],
			'BOOLEANS_AS_STRING'       => [['true' => 'true', 'false' => 'false', 'array' => ['value' => 'false']], BOOLEANS_AS_STRING, true],
		];
	}

	/**
	 * @dataProvider queryParamDataProvider
	 *
	 * @param array $expected
	 * @param int   $bool_cast
	 * @param bool  $remove_empty
	 */
	public function testCleanQueryParams(array $expected, int $bool_cast, bool $remove_empty):void{
		$data = ['whatever' => null, 'nope' => '', 'true' => true, 'false' => false, 'array' => ['value' => false]];

		$this::assertSame($expected, clean_query_params($data, $bool_cast, $remove_empty));
	}

	public function mergeQueryDataProvider():array{
		$uri    = 'http://localhost/whatever/';
		$params = ['foo' => 'bar'];

		return [
			'add nothing and clear the trailing question mark' => [$uri.'?', [], $uri],
			'add to URI without query'                         => [$uri, $params, $uri.'?foo=bar'],
			'overwrite existing param'                         => [$uri.'?foo=nope', $params, $uri.'?foo=bar'],
			'add to existing param'                            => [$uri.'?what=nope', $params, $uri.'?foo=bar&what=nope'],
		];
	}

	/**
	 * @dataProvider mergeQueryDataProvider
	 *
	 * @param string $uri
	 * @param array  $params
	 * @param string $expected
	 */
	public function testMergeQuery(string $uri, array $params, string $expected):void{
		$merged = merge_query($uri, $params);
		$this::assertSame($expected, $merged);
	}

	public function rawurlencodeDataProvider():array{
		return [
			'null'         => [null, ''],
			'bool (false)' => [false, ''],
			'bool (true)'  => [true, '1'],
			'int'          => [42, '42'],
			'float'        => [42.42, '42.42'],
			'string'       => ['some test string!', 'some%20test%20string%21'],
			'array'        => [
				['some other', 'test string', ['oh wait!', 'this', ['is an', 'array!']]],
				['some%20other', 'test%20string', ['oh%20wait%21', 'this', ['is%20an', 'array%21']]],
			],
		];
	}

	/**
	 * @dataProvider rawurlencodeDataProvider
	 *
	 * @param $data
	 * @param $expected
	 */
	public function testRawurlencode($data, $expected):void{
		$this::assertSame($expected, r_rawurlencode($data));
	}

	public function testRawurlencodeTypeErrorException():void{
		$this::expectException(TypeError::class);

		r_rawurlencode(new \stdClass());
	}

	public function testBuildHttpQuery():void{

		$data = ['foo' => 'bar', 'whatever?' => 'nope!'];

		$this::assertSame('', build_http_query([]));
		$this::assertSame('foo=bar&whatever%3F=nope%21', build_http_query($data));
		$this::assertSame('foo=bar&whatever?=nope!', build_http_query($data, false));
		$this::assertSame('foo=bar, whatever?=nope!', build_http_query($data, false, ', '));
		$this::assertSame('foo="bar", whatever?="nope!"', build_http_query($data, false, ', ', '"'));

		$data['florps']  = ['nope', 'nope', 'nah'];
		$this::assertSame('florps="nah", florps="nope", florps="nope", foo="bar", whatever?="nope!"', build_http_query($data, false, ', ', '"'));
	}

	public function testGetJSON():void{

		$r = (new Response)->withBody(create_stream('{"foo":"bar"}'));

		$this::assertSame('bar', get_json($r)->foo);

		$r->getBody()->rewind();

		$this::assertSame('bar', get_json($r, true)['foo']);
	}

	public function testGetXML():void{

		$r = (new Response)->withBody(create_stream('<?xml version="1.0" encoding="UTF-8"?><root><foo>bar</foo></root>'));

		$this::assertSame('bar', get_xml($r)->foo->__toString());

		$r->getBody()->rewind();

		$this::assertSame('bar', get_xml($r, true)['foo']);
	}

	public function messageDataProvider():array{
		return [
			'Request'  => [new Request('GET', 'https://localhost/foo'), 'GET /foo HTTP/1.1'."\r\n".'Host: localhost'."\r\n".'foo: bar'."\r\n\r\n".'testbody'],
			'Response' => [new Response, 'HTTP/1.1 200 OK'."\r\n".'foo: bar'."\r\n\r\n".'testbody'],
		];
	}

	/**
	 * @dataProvider messageDataProvider
	 *
	 * @param \Psr\Http\Message\MessageInterface $message
	 * @param string                             $expected
	 */
	public function testMessageToString(MessageInterface $message, string $expected):void{
		$this::assertSame(
			$expected,
			message_to_string($message->withAddedHeader('foo', 'bar')->withBody(create_stream('testbody')))
		);
	}

	public function decompressDataProvider():array{
		return [
			'compress' => ['gzcompress', 'compress'],
			'deflate'  => ['gzdeflate', 'deflate'],
			'gzip'     => ['gzencode', 'gzip'],
			'none'     => ['', ''],
		];
	}

	/**
	 * @dataProvider decompressDataProvider
	 */
	public function testDecompressContent(string $fn, string $encoding):void{
		$data = $expected = str_repeat('compressed string ', 100);
		$response = (new Response);

		if($fn){
			$data     = $fn($data);
			$response = $response->withHeader('Content-Encoding', $encoding);
		}

		$response = $response->withBody(create_stream($data));

		$this::assertSame($expected, decompress_content($response));
	}

	public function testUriIsAbsolute():void{
		$this::assertTrue(uriIsAbsolute(new Uri('http://example.org')));
		$this::assertFalse(uriIsAbsolute(new Uri('//example.org')));
		$this::assertFalse(uriIsAbsolute(new Uri('/abs-path')));
		$this::assertFalse(uriIsAbsolute(new Uri('rel-path')));
	}

	public function testUriIsNetworkPathReference():void{
		$this::assertFalse(uriIsNetworkPathReference(new Uri('http://example.org')));
		$this::assertTrue(uriIsNetworkPathReference(new Uri('//example.org')));
		$this::assertFalse(uriIsNetworkPathReference(new Uri('/abs-path')));
		$this::assertFalse(uriIsNetworkPathReference(new Uri('rel-path')));
	}

	public function testUriIsAbsolutePathReference():void{
		$this::assertFalse(uriIsAbsolutePathReference(new Uri('http://example.org')));
		$this::assertFalse(uriIsAbsolutePathReference(new Uri('//example.org')));
		$this::assertTrue(uriIsAbsolutePathReference(new Uri('/abs-path')));
		$this::assertTrue(uriIsAbsolutePathReference(new Uri('/')));
		$this::assertFalse(uriIsAbsolutePathReference(new Uri('rel-path')));
	}

	public function testUriIsRelativePathReference():void{
		$this::assertFalse(uriIsRelativePathReference(new Uri('http://example.org')));
		$this::assertFalse(uriIsRelativePathReference(new Uri('//example.org')));
		$this::assertFalse(uriIsRelativePathReference(new Uri('/abs-path')));
		$this::assertTrue(uriIsRelativePathReference(new Uri('rel-path')));
		$this::assertTrue(uriIsRelativePathReference(new Uri('')));
	}

	public function testUriAddAndRemoveQueryValues():void{
		$uri = new Uri;

		$uri = uriWithQueryValue($uri, 'a', 'b');
		$uri = uriWithQueryValue($uri, 'c', 'd');
		$uri = uriWithQueryValue($uri, 'e', null);
		$this::assertSame('a=b&c=d&e', $uri->getQuery());

		$uri = uriWithoutQueryValue($uri, 'c');
		$this::assertSame('a=b&e', $uri->getQuery());
		$uri = uriWithoutQueryValue($uri, 'e');
		$this::assertSame('a=b', $uri->getQuery());
		$uri = uriWithoutQueryValue($uri, 'a');
		$this::assertSame('', $uri->getQuery());
	}

	public function testUriWithQueryValueReplacesSameKeys():void{
		$uri = new Uri;

		$uri = uriWithQueryValue($uri, 'a', 'b');
		$uri = uriWithQueryValue($uri, 'c', 'd');
		$uri = uriWithQueryValue($uri, 'a', 'e');
		$this::assertSame('c=d&a=e', $uri->getQuery());
	}

	public function testUriWithoutQueryValueRemovesAllSameKeys():void{
		$uri = (new Uri)->withQuery('a=b&c=d&a=e');

		$uri = uriWithoutQueryValue($uri, 'a');
		$this::assertSame('c=d', $uri->getQuery());
	}

	public function testUriRemoveNonExistingQueryValue():void{
		$uri = new Uri;
		$uri = uriWithQueryValue($uri, 'a', 'b');
		$uri = uriWithoutQueryValue($uri, 'c');
		$this::assertSame('a=b', $uri->getQuery());
	}

	public function testUriWithQueryValueHandlesEncoding():void{
		$uri = new Uri;
		$uri = uriWithQueryValue($uri, 'E=mc^2', 'ein&stein');
		$this::assertSame('E%3Dmc%5E2=ein%26stein', $uri->getQuery(), 'Decoded key/value get encoded');

		$uri = new Uri;
		$uri = uriWithQueryValue($uri, 'E%3Dmc%5e2', 'ein%26stein');
		$this::assertSame('E%3Dmc%5e2=ein%26stein', $uri->getQuery(), 'Encoded key/value do not get double-encoded');
	}

	public function testUriWithoutQueryValueHandlesEncoding():void{
		// It also tests that the case of the percent-encoding does not matter,
		// i.e. both lowercase "%3d" and uppercase "%5E" can be removed.
		$uri = (new Uri)->withQuery('E%3dmc%5E2=einstein&foo=bar');
		$uri = uriWithoutQueryValue($uri, 'E=mc^2');
		$this::assertSame('foo=bar', $uri->getQuery(), 'Handles key in decoded form');

		$uri = (new Uri)->withQuery('E%3dmc%5E2=einstein&foo=bar');
		$uri = uriWithoutQueryValue($uri, 'E%3Dmc%5e2');
		$this::assertSame('foo=bar', $uri->getQuery(), 'Handles key in encoded form');

		$uri = uriWithoutQueryValue(uriWithoutQueryValue($uri, 'foo'), ''); // coverage
		$this::assertSame('', $uri->getQuery());
	}

}
