<?php
/**
 *
 * @filesource   UriExtendedTest.php
 * @created      06.03.2019
 * @package      chillerlan\HTTPTest\Psr7
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\UriExtended;
use PHPUnit\Framework\TestCase;

/**
 * Class UriExtendedTest
 */
class UriExtendedTest extends TestCase{

	public function testIsAbsolute(){
		$this::assertTrue((new UriExtended('http://example.org'))->isAbsolute());
		$this::assertFalse((new UriExtended('//example.org'))->isAbsolute());
		$this::assertFalse((new UriExtended('/abs-path'))->isAbsolute());
		$this::assertFalse((new UriExtended('rel-path'))->isAbsolute());
	}

	public function testIsNetworkPathReference(){
		$this::assertFalse((new UriExtended('http://example.org'))->isNetworkPathReference());
		$this::assertTrue((new UriExtended('//example.org'))->isNetworkPathReference());
		$this::assertFalse((new UriExtended('/abs-path'))->isNetworkPathReference());
		$this::assertFalse((new UriExtended('rel-path'))->isNetworkPathReference());
	}

	public function testIsAbsolutePathReference(){
		$this::assertFalse((new UriExtended('http://example.org'))->isAbsolutePathReference());
		$this::assertFalse((new UriExtended('//example.org'))->isAbsolutePathReference());
		$this::assertTrue((new UriExtended('/abs-path'))->isAbsolutePathReference());
		$this::assertTrue((new UriExtended('/'))->isAbsolutePathReference());
		$this::assertFalse((new UriExtended('rel-path'))->isAbsolutePathReference());
	}

	public function testIsRelativePathReference(){
		$this::assertFalse((new UriExtended('http://example.org'))->isRelativePathReference());
		$this::assertFalse((new UriExtended('//example.org'))->isRelativePathReference());
		$this::assertFalse((new UriExtended('/abs-path'))->isRelativePathReference());
		$this::assertTrue((new UriExtended('rel-path'))->isRelativePathReference());
		$this::assertTrue((new UriExtended(''))->isRelativePathReference());
	}

	public function testAddAndRemoveQueryValues(){
		$uri = new UriExtended;
		/** @var UriExtended $uri */
		$uri = $uri->withQueryValue('a', 'b');
		$uri = $uri->withQueryValue('c', 'd');
		$uri = $uri->withQueryValue('e', null);
		$this::assertSame('a=b&c=d&e', $uri->getQuery());

		$uri = $uri->withoutQueryValue('c');
		$this::assertSame('a=b&e', $uri->getQuery());
		$uri = $uri->withoutQueryValue('e');
		$this::assertSame('a=b', $uri->getQuery());
		$uri = $uri->withoutQueryValue('a');
		$this::assertSame('', $uri->getQuery());
	}

	public function testWithQueryValueReplacesSameKeys(){
		$uri = new UriExtended;
		/** @var UriExtended $uri */
		$uri = $uri->withQueryValue('a', 'b');
		$uri = $uri->withQueryValue('c', 'd');
		$uri = $uri->withQueryValue('a', 'e');
		$this::assertSame('c=d&a=e', $uri->getQuery());
	}

	public function testWithoutQueryValueRemovesAllSameKeys(){
		$uri = (new UriExtended)->withQuery('a=b&c=d&a=e');
		/** @var UriExtended $uri */
		$uri = $uri->withoutQueryValue('a');
		$this::assertSame('c=d', $uri->getQuery());
	}

	public function testRemoveNonExistingQueryValue(){
		$uri = new UriExtended;
		$uri = $uri->withQueryValue('a', 'b');
		$uri = $uri->withoutQueryValue('c');
		$this::assertSame('a=b', $uri->getQuery());
	}

	public function testWithQueryValueHandlesEncoding(){
		$uri = new UriExtended;
		$uri = $uri->withQueryValue('E=mc^2', 'ein&stein');
		$this::assertSame('E%3Dmc%5E2=ein%26stein', $uri->getQuery(), 'Decoded key/value get encoded');

		$uri = new UriExtended;
		$uri = $uri->withQueryValue('E%3Dmc%5e2', 'ein%26stein');
		$this::assertSame('E%3Dmc%5e2=ein%26stein', $uri->getQuery(), 'Encoded key/value do not get double-encoded');
	}

	public function testWithoutQueryValueHandlesEncoding(){
		// It also tests that the case of the percent-encoding does not matter,
		// i.e. both lowercase "%3d" and uppercase "%5E" can be removed.
		$uri = (new UriExtended)->withQuery('E%3dmc%5E2=einstein&foo=bar');
		/** @var UriExtended $uri */
		$uri = $uri->withoutQueryValue('E=mc^2');
		$this::assertSame('foo=bar', $uri->getQuery(), 'Handles key in decoded form');

		$uri = (new UriExtended)->withQuery('E%3dmc%5E2=einstein&foo=bar');
		/** @var UriExtended $uri */
		$uri = $uri->withoutQueryValue('E%3Dmc%5e2');
		$this::assertSame('foo=bar', $uri->getQuery(), 'Handles key in encoded form');

		$uri = $uri->withoutQueryValue('foo')->withoutQueryValue(''); // coverage
		$this::assertSame('', $uri->getQuery());
	}

}
