<?php
/**
 * Class MessageHelpersTest
 *
 * @filesource   MessageHelpersTest.php
 * @created      01.09.2018
 * @package      chillerlan\HTTPTest\Psr7
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\{Psr17, Psr7, Psr7\Request, Psr7\Response};
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{MessageInterface};

class MessageHelpersTest extends TestCase{

	public function headerDataProvider():array {
		return [
			'content-Type'  => [['Content-Type' => 'application/x-www-form-urlencoded'], ['content-type' => 'application/x-www-form-urlencoded']],
			'lowercasekey'  => [['lowercasekey' => 'lowercasevalue'], ['lowercasekey' => 'lowercasevalue']],
			'UPPERCASEKEY'  => [['UPPERCASEKEY' => 'UPPERCASEVALUE'], ['uppercasekey' => 'UPPERCASEVALUE']],
			'mIxEdCaSeKey'  => [['mIxEdCaSeKey' => 'MiXeDcAsEvAlUe'], ['mixedcasekey' => 'MiXeDcAsEvAlUe']],
			'31i71casekey'  => [['31i71casekey' => '31i71casevalue'], ['31i71casekey' => '31i71casevalue']],
			'numericvalue'  => [['numericvalue:1'], ['numericvalue'  => '1']],
			'arrayvalue'    => [[['foo' => 'bar']], ['foo' => 'bar']],
			'invalid: 2'    => [[2 => 2], []],
			'invalid: what' => [['what'], []],
		];
	}

	/**
	 * @dataProvider headerDataProvider
	 *
	 * @param array $header
	 * @param array $normalized
	 */
	public function testNormalizeHeaders(array $header, array $normalized){
		$this->assertSame($normalized, Psr7\normalize_request_headers($header));
	}

	public function queryParamDataProvider(){
		return [
			// don't remove empty values
			'BOOLEANS_AS_BOOL (clean)' => [['whatever' => null, 'nope' => '', 'true' => true, 'false' => false, 'array' => ['value' => false]], Psr7\BOOLEANS_AS_BOOL, false],
			// bool cast to types
			'BOOLEANS_AS_BOOL'         => [['true' => true, 'false' => false, 'array' => ['value' => false]], Psr7\BOOLEANS_AS_BOOL, true],
			'BOOLEANS_AS_INT'          => [['true' => 1, 'false' => 0, 'array' => ['value' => 0]], Psr7\BOOLEANS_AS_INT, true],
			'BOOLEANS_AS_INT_STRING'   => [['true' => '1', 'false' => '0', 'array' => ['value' => '0']], Psr7\BOOLEANS_AS_INT_STRING, true],
			'BOOLEANS_AS_STRING'       => [['true' => 'true', 'false' => 'false', 'array' => ['value' => 'false']], Psr7\BOOLEANS_AS_STRING, true],
		];
	}

	/**
	 * @dataProvider queryParamDataProvider
	 *
	 * @param array $expected
	 * @param int   $bool_cast
	 * @param bool  $remove_empty
	 */
	public function testCleanQueryParams(array $expected, int $bool_cast, bool $remove_empty){
		$data = ['whatever' => null, 'nope' => '', 'true' => true, 'false' => false, 'array' => ['value' => false]];

		$this->assertSame($expected, Psr7\clean_query_params($data, $bool_cast, $remove_empty));
	}

	public function rawurlencodeDataProvider(){
		return [
			'string' => ['some test string!', 'some%20test%20string%21'],
			'array'  => [['some other', 'test string', ['oh wait!', 'this', ['is an', 'array!']]], ['some%20other', 'test%20string', ['oh%20wait%21', 'this', ['is%20an', 'array%21']]]],
		];
	}

	/**
	 * @dataProvider rawurlencodeDataProvider
	 *
	 * @param $data
	 * @param $expected
	 */
	public function testRawurlencode($data, $expected){
		$this->assertSame($expected, Psr7\r_rawurlencode($data));
	}

	public function testBuildHttpQuery(){

		$data = ['foo' => 'bar', 'whatever?' => 'nope!'];

		$this->assertSame('', Psr7\build_http_query([]));
		$this->assertSame('foo=bar&whatever%3F=nope%21', Psr7\build_http_query($data));
		$this->assertSame('foo=bar&whatever?=nope!', Psr7\build_http_query($data, false));
		$this->assertSame('foo=bar, whatever?=nope!', Psr7\build_http_query($data, false, ', '));
		$this->assertSame('foo="bar", whatever?="nope!"', Psr7\build_http_query($data, false, ', ', '"'));

		$data['florps']  = ['nope', 'nope', 'nah'];
		$this->assertSame('florps="nah", florps="nope", florps="nope", foo="bar", whatever?="nope!"', Psr7\build_http_query($data, false, ', ', '"'));
	}

	public function testGetJSON(){

		$r = (new Response)
			->withBody(Psr17\create_stream('{"foo":"bar"}'));

		$this->assertSame('bar', Psr7\get_json($r)->foo);

		$r->getBody()->rewind();

		$this->assertSame('bar', Psr7\get_json($r, true)['foo']);
	}

	public function testGetXML(){

		$r = (new Response)
			->withBody(Psr17\create_stream('<?xml version="1.0" encoding="UTF-8"?><root><foo>bar</foo></root>'));

		$this->assertSame('bar', Psr7\get_xml($r)->foo->__toString());

		$r->getBody()->rewind();

		$this->assertSame('bar', Psr7\get_xml($r, true)['foo']);
	}

	public function messageDataProvider(){
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
	public function testMessageToString(MessageInterface $message, string $expected){
		$this->assertSame(
			$expected,
			Psr7\message_to_string($message->withAddedHeader('foo', 'bar')->withBody(Psr17\create_stream('testbody')))
		);
	}

	public function decompressDataProvider(){
		return [
			'compress' => ['gzcompress', 'compress'],
			'deflate'  => ['gzdeflate', 'deflate'],
			'gzip'     => ['gzencode', 'gzip'],
			'none'     => [null, null],
		];
	}

	/**
	 * @dataProvider decompressDataProvider
	 */
	public function testDecompressContent($fn, $encoding){
		$data = $expected = str_repeat('compressed string ', 100);
		$response = (new Response);

		if($fn){
			$data     = $fn($data);
			$response = $response->withHeader('Content-Encoding', $encoding);
		}

		$response = $response->withBody(Psr17\create_stream($data));

		$this->assertSame($expected, Psr7\decompress_content($response));
	}
}
