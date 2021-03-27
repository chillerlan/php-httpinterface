<?php
/**
 * Class QueryTest
 *
 * @created      27.03.2021
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2021 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\Query;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class QueryTest extends TestCase{

	public function queryParamDataProvider():array{
		return [
			// don't remove empty values
			'BOOLEANS_AS_BOOL (no remove)' => [
				Query::BOOLEANS_AS_BOOL,
				false,
				['whatever' => null, 'nope' => '', 'true' => true, 'false' => false, 'array' => ['value' => false]],
			],
			// bool cast to types
			'BOOLEANS_AS_BOOL' => [
				Query::BOOLEANS_AS_BOOL,
				true,
				['true' => true, 'false' => false, 'array' => ['value' => false]],
			],
			'BOOLEANS_AS_INT' => [
				Query::BOOLEANS_AS_INT,
				true,
				['true' => 1, 'false' => 0, 'array' => ['value' => 0]],
			],
			'BOOLEANS_AS_INT_STRING' => [
				Query::BOOLEANS_AS_INT_STRING,
				true,
				['true' => '1', 'false' => '0', 'array' => ['value' => '0']],
			],
			'BOOLEANS_AS_STRING' => [
				Query::BOOLEANS_AS_STRING,
				true,
				['true' => 'true', 'false' => 'false', 'array' => ['value' => 'false']],
			],
		];
	}

	/**
	 * @dataProvider queryParamDataProvider
	 *
	 * @param array $expected
	 * @param int   $bool_cast
	 * @param bool  $remove_empty
	 */
	public function testCleanQueryParams(int $bool_cast, bool $remove_empty, array $expected):void{
		$data = ['whatever' => null, 'nope' => '', 'true' => true, 'false' => false, 'array' => ['value' => false]];

		$this::assertSame($expected, Query::cleanParams($data, $bool_cast, $remove_empty));
	}

	public function testBuildHttpQuery():void{

		$data = ['foo' => 'bar', 'whatever?' => 'nope!'];

		$this::assertSame('', Query::build([]));
		$this::assertSame('foo=bar&whatever%3F=nope%21', Query::build($data));
		$this::assertSame('foo=bar&whatever?=nope!', Query::build($data, false));
		$this::assertSame('foo=bar, whatever?=nope!', Query::build($data, false, ', '));
		$this::assertSame('foo="bar", whatever?="nope!"', Query::build($data, false, ', ', '"'));

		$data['florps']  = ['nope', 'nope', 'nah'];
		$this::assertSame('florps="nah", florps="nope", florps="nope", foo="bar", whatever?="nope!"', Query::build($data, false, ', ', '"'));
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
		$merged = Query::merge($uri, $params);
		$this::assertSame($expected, $merged);
	}


}
