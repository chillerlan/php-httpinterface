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

use PHPUnit\Framework\TestCase;
use chillerlan\HTTP\Psr7;

class MessageHelpersTest extends TestCase{

	public function headerDataProvider():array {
		return [
			'content-Type'  => [['content-Type' => 'application/x-www-form-urlencoded'], ['Content-type' => 'application/x-www-form-urlencoded']],
			'lowercasekey'  => [['lowercasekey' => 'lowercasevalue'], ['Lowercasekey' => 'lowercasevalue']],
			'UPPERCASEKEY'  => [['UPPERCASEKEY' => 'UPPERCASEVALUE'], ['Uppercasekey' => 'UPPERCASEVALUE']],
			'mIxEdCaSeKey'  => [['mIxEdCaSeKey' => 'MiXeDcAsEvAlUe'], ['Mixedcasekey' => 'MiXeDcAsEvAlUe']],
			'31i71casekey'  => [['31i71casekey' => '31i71casevalue'], ['31i71casekey' => '31i71casevalue']],
			'numericvalue'  => [[1 => 'numericvalue:1'], ['Numericvalue'  => '1']],
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

	public function testCheckParams(){
		$data = ['foo' => 'bar', 'whatever' => null, 'nope' => '', 'true' => true, 'false' => false];

		$this->assertSame(['foo' => 'bar', 'true' => '1', 'false' => '0'], Psr7\clean_query_params($data));
		$this->assertSame(['foo' => 'bar', 'true' => 'true', 'false' => 'false'], Psr7\clean_query_params($data, true));
	}


	public function rawurlencodeDataProvider(){
		return [
			'string' => ['some test string!', 'some%20test%20string%21'],
			'array'  => [['some other', 'test string', ['oh wait!', 'this', ['is an', 'array!']]], ['some%20other', 'test%20string', ['oh%20wait%21', 'this', ['is%20an', 'array%21']]]],
		];
	}

	/**
	 * @dataProvider rawurlencodeDataProvider
	 */
	public function testRawurlencode($data, $expected){
		$this->assertSame($expected, Psr7\raw_urlencode($data));
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

}
