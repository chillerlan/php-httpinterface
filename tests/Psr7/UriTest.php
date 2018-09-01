<?php
/**
 * Class UriTest
 *
 * @link https://github.com/guzzle/psr7/blob/4b981cdeb8c13d22a6c193554f8c686f53d5c958/tests/UriTest.php
 * @link https://github.com/bakame-php/psr7-uri-interface-tests/blob/5a556fdfe668a6c6a14772efeba6134c0b7dae34/tests/AbstractUriTestCase.php
 *
 * @filesource   UriTest.php
 * @created      10.08.2018
 * @package      chillerlan\HTTPTest\Psr7
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\Uri;
use chillerlan\HTTP\Psr17;
use chillerlan\HTTP\Psr17\UriFactory;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use PHPUnit\Framework\TestCase;

class UriTest extends TestCase{

	/**
	 * @var \chillerlan\HTTP\Psr17\UriFactory
	 */
	protected $uriFactory;

	protected function setUp(){
		$this->uriFactory = new UriFactory;
	}

	public function testParsesProvidedUri(){
		$uri = $this->uriFactory->createUri('https://user:pass@example.com:8080/path/123?q=abc#test'); // URIFactory coverage

		$this->assertSame('https', $uri->getScheme());
		$this->assertSame('user:pass@example.com:8080', $uri->getAuthority());
		$this->assertSame('user:pass', $uri->getUserInfo());
		$this->assertSame('example.com', $uri->getHost());
		$this->assertSame(8080, $uri->getPort());
		$this->assertSame('/path/123', $uri->getPath());
		$this->assertSame('q=abc', $uri->getQuery());
		$this->assertSame('test', $uri->getFragment());
		$this->assertSame('https://user:pass@example.com:8080/path/123?q=abc#test', (string)$uri);
	}

	public function testCanTransformAndRetrievePartsIndividually(){
		$uri = (new Uri)
			->withScheme('https')
			->withUserInfo('user', 'pass')
			->withHost('example.com')
			->withPort(8080)
			->withPath('/path/123')
			->withQuery('q=abc')
			->withFragment('test')
		;

		$this->assertSame('https', $uri->getScheme());
		$this->assertSame('user:pass@example.com:8080', $uri->getAuthority());
		$this->assertSame('user:pass', $uri->getUserInfo());
		$this->assertSame('example.com', $uri->getHost());
		$this->assertSame(8080, $uri->getPort());
		$this->assertSame('/path/123', $uri->getPath());
		$this->assertSame('q=abc', $uri->getQuery());
		$this->assertSame('test', $uri->getFragment());
		$this->assertSame('https://user:pass@example.com:8080/path/123?q=abc#test', (string)$uri);
	}

	public function getValidUris(){
		return [
			['urn:path-rootless'],
			['urn:path:with:colon'],
			['urn:/path-absolute'],
			['urn:/'],
			// only scheme with empty path
			['urn:'],
			// only path
			['/'],
			['relative/'],
			['0'],
			// same document reference
			[''],
			// network path without scheme
			['//example.org'],
			['//example.org/'],
			['//example.org?q#h'],
			// only query
			['?q'],
			['?q=abc&foo=bar'],
			// only fragment
			['#fragment'],
			// dot segments are not removed automatically
			['./foo/../bar'],
		];
	}

	/**
	 * @dataProvider getValidUris
	 */
	public function testValidUrisStayValid($input){
		$this->assertSame($input, (string)(new Uri($input)));
	}

	/**
	 * @dataProvider getValidUris
	 */
	public function testFromParts($input){
		$this->assertSame($input, (string)(new Uri)->fromParts(parse_url($input)));
	}

	public function getInvalidUris(){
		return [
			// parse_url() requires the host component which makes sense for http(s)
			// but not when the scheme is not known or different. So '//' or '///' is
			// currently invalid as well but should not according to RFC 3986.
			'only scheme'     => ['http://'],
			// host cannot contain ":"
			'host with colon' => ['urn://host:with:colon'],
		];
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage invalid URI
	 * @dataProvider             getInvalidUris
	 */
	public function testInvalidUrisThrowException($invalidUri){
		new Uri($invalidUri);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage invalid port: 82517
	 */
	public function testPortMustBeValid(){
		(new Uri)->withPort(82517);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage invalid port: 0
	 */
	public function testWithPortCannotBeZero(){
		(new Uri)->withPort(0);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage invalid URI: "//example.com:0
	 */
	public function testParseUriPortCannotBeZero(){
		new Uri('//example.com:0');
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testSchemeMustHaveCorrectType(){
		(new Uri)->withScheme([]);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testHostMustHaveCorrectType(){
		(new Uri)->withHost([]);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testPathMustHaveCorrectType(){
		(new Uri)->withPath([]);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testQueryMustHaveCorrectType(){
		(new Uri)->withQuery([]);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testFragmentMustHaveCorrectType(){
		(new Uri)->withFragment([]);
	}

	public function testCanParseFalseyUriParts(){
		$uri = new Uri('0://0:0@0/0?0#0');

		$this->assertSame('0', $uri->getScheme());
		$this->assertSame('0:0@0', $uri->getAuthority());
		$this->assertSame('0:0', $uri->getUserInfo());
		$this->assertSame('0', $uri->getHost());
		$this->assertSame('/0', $uri->getPath());
		$this->assertSame('0', $uri->getQuery());
		$this->assertSame('0', $uri->getFragment());
		$this->assertSame('0://0:0@0/0?0#0', (string)$uri);
	}

	public function testCanConstructFalseyUriParts(){
		$uri = (new Uri)
			->withScheme('0')
			->withUserInfo('0', '0')
			->withHost('0')
			->withPath('/0')
			->withQuery('0')
			->withFragment('0')
		;

		$this->assertSame('0', $uri->getScheme());
		$this->assertSame('0:0@0', $uri->getAuthority());
		$this->assertSame('0:0', $uri->getUserInfo());
		$this->assertSame('0', $uri->getHost());
		$this->assertSame('/0', $uri->getPath());
		$this->assertSame('0', $uri->getQuery());
		$this->assertSame('0', $uri->getFragment());
		$this->assertSame('0://0:0@0/0?0#0', (string)$uri);
	}

	public function testSchemeIsNormalizedToLowercase(){
		$uri = new Uri('HTTP://example.com');

		$this->assertSame('http', $uri->getScheme());
		$this->assertSame('http://example.com', (string)$uri);

		$uri = (new Uri('//example.com'))->withScheme('HTTP');

		$this->assertSame('http', $uri->getScheme());
		$this->assertSame('http://example.com', (string)$uri);
	}

	public function testHostIsNormalizedToLowercase(){
		$uri = new Uri('//eXaMpLe.CoM');

		$this->assertSame('example.com', $uri->getHost());
		$this->assertSame('//example.com', (string)$uri);

		$uri = (new Uri)->withHost('eXaMpLe.CoM');

		$this->assertSame('example.com', $uri->getHost());
		$this->assertSame('//example.com', (string)$uri);
	}

	public function testPortIsNullIfStandardPortForScheme(){
		// HTTPS standard port
		$uri = new Uri('https://example.com:443');
		$this->assertNull($uri->getPort());
		$this->assertSame('example.com', $uri->getAuthority());

		$uri = (new Uri('https://example.com'))->withPort(443);
		$this->assertNull($uri->getPort());
		$this->assertSame('example.com', $uri->getAuthority());

		// HTTP standard port
		$uri = new Uri('http://example.com:80');
		$this->assertNull($uri->getPort());
		$this->assertSame('example.com', $uri->getAuthority());

		$uri = (new Uri('http://example.com'))->withPort(80);
		$this->assertNull($uri->getPort());
		$this->assertSame('example.com', $uri->getAuthority());
	}

	public function testPortIsReturnedIfSchemeUnknown(){
		$uri = (new Uri('//example.com'))->withPort(80);

		$this->assertSame(80, $uri->getPort());
		$this->assertSame('example.com:80', $uri->getAuthority());
	}

	public function testStandardPortIsNullIfSchemeChanges(){
		$uri = new Uri('http://example.com:443');
		$this->assertSame('http', $uri->getScheme());
		$this->assertSame(443, $uri->getPort());

		$uri = $uri->withScheme('https');
		$this->assertNull($uri->getPort());
	}

	public function testPortPassedAsStringIsCastedToInt(){
		$uri = (new Uri('//example.com'))->withPort('8080');

		$this->assertSame(8080, $uri->getPort(), 'Port is returned as integer');
		$this->assertSame('example.com:8080', $uri->getAuthority());
	}

	public function testPortCanBeRemoved(){
		$uri = (new Uri('http://example.com:8080'))->withPort(null);

		$this->assertNull($uri->getPort());
		$this->assertSame('http://example.com', (string)$uri);
	}

	public function uriComponentsEncodingProvider(){
		$unreserved = 'a-zA-Z0-9.-_~!$&\'()*+,;=:@';

		return [
			'Percent encode spaces' => [
				'/pa th?q=va lue#frag ment',
				'/pa%20th',
				'q=va%20lue',
				'frag%20ment',
				'/pa%20th?q=va%20lue#frag%20ment',
			],
			'Percent encode multibyte' => [
				'/€?€#€',
				'/%E2%82%AC',
				'%E2%82%AC',
				'%E2%82%AC',
				'/%E2%82%AC?%E2%82%AC#%E2%82%AC',
			],
			'Don\'t encode already encoded' => [
				'/pa%20th?q=va%20lue#frag%20ment',
				'/pa%20th',
				'q=va%20lue',
				'frag%20ment',
				'/pa%20th?q=va%20lue#frag%20ment',
			],
			'Percent encode invalid percent encodings' => [
				'/pa%2-th?q=va%2-lue#frag%2-ment',
				'/pa%252-th',
				'q=va%252-lue',
				'frag%252-ment',
				'/pa%252-th?q=va%252-lue#frag%252-ment',
			],
			'Don\'t encode path segments' => [
				'/pa/th//two?q=va/lue#frag/ment',
				'/pa/th//two',
				'q=va/lue',
				'frag/ment',
				'/pa/th//two?q=va/lue#frag/ment',
			],
			'Don\'t encode unreserved chars or sub-delimiters' => [
				"/$unreserved?$unreserved#$unreserved",
				"/$unreserved",
				$unreserved,
				$unreserved,
				"/$unreserved?$unreserved#$unreserved",
			],
			'Encoded unreserved chars are not decoded' => [
				'/p%61th?q=v%61lue#fr%61gment',
				'/p%61th',
				'q=v%61lue',
				'fr%61gment',
				'/p%61th?q=v%61lue#fr%61gment',
			],
		];
	}

	/**
	 * @dataProvider uriComponentsEncodingProvider
	 */
	public function testUriComponentsGetEncodedProperly($input, $path, $query, $fragment, $output){
		$uri = new Uri($input);

		$this->assertSame($path, $uri->getPath());
		$this->assertSame($query, $uri->getQuery());
		$this->assertSame($fragment, $uri->getFragment());
		$this->assertSame($output, (string)$uri);
	}

	/**
	 * In RFC 8986 the host is optional and the authority can only
	 * consist of the user info and port.
	 */
	public function testAuthorityWithUserInfoOrPortButWithoutHost(){
		$uri = (new Uri)->withUserInfo('user', 'pass');

		$this->assertSame('user:pass', $uri->getUserInfo());
		$this->assertSame('user:pass@', $uri->getAuthority());

		$uri = $uri->withPort(8080);
		$this->assertSame(8080, $uri->getPort());
		$this->assertSame('user:pass@:8080', $uri->getAuthority());
		$this->assertSame('//user:pass@:8080', (string)$uri);

		$uri = $uri->withUserInfo('');
		$this->assertSame(':8080', $uri->getAuthority());
	}

	public function testHostInHttpUriDefaultsToLocalhost(){
		$uri = (new Uri)->withScheme('http');

		$this->assertSame('localhost', $uri->getHost());
		$this->assertSame('localhost', $uri->getAuthority());
		$this->assertSame('http://localhost', (string)$uri);
	}

	public function testHostInHttpsUriDefaultsToLocalhost(){
		$uri = (new Uri)->withScheme('https');

		$this->assertSame('localhost', $uri->getHost());
		$this->assertSame('localhost', $uri->getAuthority());
		$this->assertSame('https://localhost', (string)$uri);
	}

	public function testFileSchemeWithEmptyHostReconstruction(){
		$uri = new Uri('file:///tmp/filename.ext');

		$this->assertSame('', $uri->getHost());
		$this->assertSame('', $uri->getAuthority());
		$this->assertSame('file:///tmp/filename.ext', (string)$uri);
	}

	public function testWithPathEncodesProperly(){
		$uri = (new Uri)->withPath('/baz?#€/b%61r');
		// Query and fragment delimiters and multibyte chars are encoded.
		$this->assertSame('/baz%3F%23%E2%82%AC/b%61r', $uri->getPath());
		$this->assertSame('/baz%3F%23%E2%82%AC/b%61r', (string)$uri);
	}

	public function testWithQueryEncodesProperly(){
		$uri = (new Uri)->withQuery('?=#&€=/&b%61r');
		// A query starting with a "?" is valid and must not be magically removed. Otherwise it would be impossible to
		// construct such an URI. Also the "?" and "/" does not need to be encoded in the query.
		$this->assertSame('?=%23&%E2%82%AC=/&b%61r', $uri->getQuery());
		$this->assertSame('??=%23&%E2%82%AC=/&b%61r', (string)$uri);
	}

	public function testWithFragmentEncodesProperly(){
		$uri = (new Uri)->withFragment('#€?/b%61r');
		// A fragment starting with a "#" is valid and must not be magically removed. Otherwise it would be impossible to
		// construct such an URI. Also the "?" and "/" does not need to be encoded in the fragment.
		$this->assertSame('%23%E2%82%AC?/b%61r', $uri->getFragment());
		$this->assertSame('#%23%E2%82%AC?/b%61r', (string)$uri);
	}

	public function testAllowsForRelativeUri(){
		$uri = (new Uri)->withPath('foo');

		$this->assertSame('foo', $uri->getPath());
		$this->assertSame('foo', (string)$uri);
	}

	public function testRelativePathAndAuhorityIsAutomagicallyFixed(){
		// concatenating a relative path with a host doesn't work: "//example.comfoo" would be wrong
		$uri = (new Uri)->withPath('foo')->withHost('example.com');

		$this->assertSame('/foo', $uri->getPath());
		$this->assertSame('//example.com/foo', (string)$uri);
	}

	public function testPathStartingWithTwoSlashesAndNoAuthorityIsInvalid(){
		// URI "//foo" would be interpreted as network reference and thus change the original path to the host
		$this->assertSame('/foo', (string)(new Uri)->withPath('//foo'));
	}

	public function testPathStartingWithTwoSlashes(){
		$uri = new Uri('http://example.org//path-not-host.com');
		$this->assertSame('//path-not-host.com', $uri->getPath());

		$uri = $uri->withScheme('');
		$this->assertSame('//example.org//path-not-host.com', (string)$uri); // This is still valid

		$uri = $uri->withHost(''); // Now it becomes invalid
		$this->assertSame('/path-not-host.com', $uri->getPath());
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage A relative URI must not have a path beginning with a segment containing a colon
	 */
	public function testRelativeUriWithPathBeginngWithColonSegmentIsInvalid(){
		(new Uri)->withPath('mailto:foo');
	}

	public function testRelativeUriWithPathHavingColonSegment(){
		$this->assertSame('/mailto:foo', (new Uri('urn:/mailto:foo'))->withScheme('')->getPath());

		$this->expectException(InvalidArgumentException::class);
		(new Uri('urn:mailto:foo'))->withScheme('');
	}

	public function testDefaultReturnValuesOfGetters(){
		$uri = new Uri;

		$this->assertSame('', $uri->getScheme());
		$this->assertSame('', $uri->getAuthority());
		$this->assertSame('', $uri->getUserInfo());
		$this->assertSame('', $uri->getHost());
		$this->assertNull($uri->getPort());
		$this->assertSame('', $uri->getPath());
		$this->assertSame('', $uri->getQuery());
		$this->assertSame('', $uri->getFragment());
	}

	public function testImmutability(){
		$uri = new Uri;

		$this->assertNotSame($uri, $uri->withScheme('https'));
		$this->assertNotSame($uri, $uri->withUserInfo('user', 'pass'));
		$this->assertNotSame($uri, $uri->withHost('example.com'));
		$this->assertNotSame($uri, $uri->withPort(8080));
		$this->assertNotSame($uri, $uri->withPath('/path/123'));
		$this->assertNotSame($uri, $uri->withQuery('q=abc'));
		$this->assertNotSame($uri, $uri->withFragment('test'));
	}

	public function testAddsSlashForRelativeUriStringWithHost(){
		// If the path is rootless and an authority is present, the path MUST
		// be prefixed by "/".
		$uri = (new Uri)->withPath('foo')->withHost('example.com');

		$this->assertSame('/foo', $uri->getPath());
		// concatenating a relative path with a host doesn't work: "//example.comfoo" would be wrong
		$this->assertSame('//example.com/foo', (string)$uri);
	}

	public function testRemoveExtraSlashesWihoutHost(){
		// If the path is starting with more than one "/" and no authority is
		// present, the starting slashes MUST be reduced to one.
		$uri = (new Uri)->withPath('//foo');

		$this->assertSame('/foo', $uri->getPath());
		// URI "//foo" would be interpreted as network reference and thus change the original path to the host
		$this->assertSame('/foo', (string)$uri);
	}

	public function hostProvider(){
		return [
			'normalized host' => [
				"MaStEr.eXaMpLe.CoM",
				"master.example.com",
			],
			"simple host"     => [
				"www.example.com",
				"www.example.com",
			],
			"IPv6 Host"       => [
				"[::1]",
				"[::1]",
			],
		];
	}

	/**
	 * The value returned MUST be normalized to lowercase, per RFC 3986
	 * Section 3.2.2.
	 *
	 * @dataProvider hostProvider
	 */
	public function testGetHost($host, $expected){
		$uri = (new Uri)->withHost($host);

		$this->assertInstanceOf(UriInterface::class, $uri);
		$this->assertSame($expected, $uri->getHost(), 'Host must be normalized according to RFC3986');
	}

	public function authorityProvider(){
		return [
			'authority'                           => [
				'scheme'    => 'http',
				'user'      => 'User',
				'pass'      => 'Pass',
				'host'      => 'master.example.com',
				'port'      => 443,
				'authority' => 'User:Pass@master.example.com:443',
			],
			'without port'                        => [
				'scheme'    => 'http',
				'user'      => 'User',
				'pass'      => 'Pass',
				'host'      => 'master.example.com',
				'port'      => null,
				'authority' => 'User:Pass@master.example.com',
			],
			'with standard port'                  => [
				'scheme'    => 'http',
				'user'      => 'User',
				'pass'      => 'Pass',
				'host'      => 'master.example.com',
				'port'      => 80,
				'authority' => 'User:Pass@master.example.com',
			],
			"authority without pass"              => [
				'scheme'    => 'http',
				'user'      => 'User',
				'pass'      => '',
				'host'      => 'master.example.com',
				'port'      => null,
				'authority' => 'User@master.example.com',
			],
			"authority without port and userinfo" => [
				'scheme'    => 'http',
				'user'      => '',
				'pass'      => '',
				'host'      => 'master.example.com',
				'port'      => null,
				'authority' => 'master.example.com',
			],
		];
	}

	/**
	 * If the port component is not set or is the standard port for the current
	 * scheme, it SHOULD NOT be included.
	 *
	 * @dataProvider authorityProvider
	 *
	 * @param string $scheme
	 * @param string $user
	 * @param string $pass
	 * @param string $host
	 * @param int    $port
	 * @param string $authority
	 */
	public function testGetAuthority(string $scheme, string $user, string $pass, string $host, $port, string $authority){
		$uri = (new Uri)
			->withHost($host)
			->withScheme($scheme)
			->withUserInfo($user, $pass)
			->withPort($port)
		;

		$this->assertSame($authority, $uri->getAuthority());
	}

	public function testIsAbsolute(){
		$this->assertTrue((new Uri('http://example.org'))->isAbsolute());
		$this->assertFalse((new Uri('//example.org'))->isAbsolute());
		$this->assertFalse((new Uri('/abs-path'))->isAbsolute());
		$this->assertFalse((new Uri('rel-path'))->isAbsolute());
	}

	public function testIsNetworkPathReference(){
		$this->assertFalse((new Uri('http://example.org'))->isNetworkPathReference());
		$this->assertTrue((new Uri('//example.org'))->isNetworkPathReference());
		$this->assertFalse((new Uri('/abs-path'))->isNetworkPathReference());
		$this->assertFalse((new Uri('rel-path'))->isNetworkPathReference());
	}

	public function testIsAbsolutePathReference(){
		$this->assertFalse((new Uri('http://example.org'))->isAbsolutePathReference());
		$this->assertFalse((new Uri('//example.org'))->isAbsolutePathReference());
		$this->assertTrue((new Uri('/abs-path'))->isAbsolutePathReference());
		$this->assertTrue((new Uri('/'))->isAbsolutePathReference());
		$this->assertFalse((new Uri('rel-path'))->isAbsolutePathReference());
	}

	public function testIsRelativePathReference(){
		$this->assertFalse((new Uri('http://example.org'))->isRelativePathReference());
		$this->assertFalse((new Uri('//example.org'))->isRelativePathReference());
		$this->assertFalse((new Uri('/abs-path'))->isRelativePathReference());
		$this->assertTrue((new Uri('rel-path'))->isRelativePathReference());
		$this->assertTrue((new Uri(''))->isRelativePathReference());
	}

	public function testAddAndRemoveQueryValues(){
		$uri = new Uri;
		/** @var Uri $uri */
		$uri = $uri->withQueryValue('a', 'b');
		$uri = $uri->withQueryValue('c', 'd');
		$uri = $uri->withQueryValue('e', null);
		$this->assertSame('a=b&c=d&e', $uri->getQuery());

		$uri = $uri->withoutQueryValue('c');
		$this->assertSame('a=b&e', $uri->getQuery());
		$uri = $uri->withoutQueryValue('e');
		$this->assertSame('a=b', $uri->getQuery());
		$uri = $uri->withoutQueryValue('a');
		$this->assertSame('', $uri->getQuery());
	}

	public function testWithQueryValueReplacesSameKeys(){
		$uri = new Uri;
		/** @var Uri $uri */
		$uri = $uri->withQueryValue('a', 'b');
		$uri = $uri->withQueryValue('c', 'd');
		$uri = $uri->withQueryValue('a', 'e');
		$this->assertSame('c=d&a=e', $uri->getQuery());
	}

	public function testWithoutQueryValueRemovesAllSameKeys(){
		$uri = (new Uri)->withQuery('a=b&c=d&a=e');
		/** @var Uri $uri */
		$uri = $uri->withoutQueryValue('a');
		$this->assertSame('c=d', $uri->getQuery());
	}

	public function testRemoveNonExistingQueryValue(){
		$uri = new Uri;
		$uri = $uri->withQueryValue('a', 'b');
		$uri = $uri->withoutQueryValue('c');
		$this->assertSame('a=b', $uri->getQuery());
	}

	public function testWithQueryValueHandlesEncoding(){
		$uri = new Uri;
		$uri = $uri->withQueryValue('E=mc^2', 'ein&stein');
		$this->assertSame('E%3Dmc%5E2=ein%26stein', $uri->getQuery(), 'Decoded key/value get encoded');

		$uri = new Uri;
		$uri = $uri->withQueryValue('E%3Dmc%5e2', 'ein%26stein');
		$this->assertSame('E%3Dmc%5e2=ein%26stein', $uri->getQuery(), 'Encoded key/value do not get double-encoded');
	}

	public function testWithoutQueryValueHandlesEncoding(){
		// It also tests that the case of the percent-encoding does not matter,
		// i.e. both lowercase "%3d" and uppercase "%5E" can be removed.
		$uri = (new Uri)->withQuery('E%3dmc%5E2=einstein&foo=bar');
		/** @var Uri $uri */
		$uri = $uri->withoutQueryValue('E=mc^2');
		$this->assertSame('foo=bar', $uri->getQuery(), 'Handles key in decoded form');

		$uri = (new Uri)->withQuery('E%3dmc%5E2=einstein&foo=bar');
		/** @var Uri $uri */
		$uri = $uri->withoutQueryValue('E%3Dmc%5e2');
		$this->assertSame('foo=bar', $uri->getQuery(), 'Handles key in encoded form');

		$uri = $uri->withoutQueryValue('foo')->withoutQueryValue(''); // coverage
		$this->assertSame('', $uri->getQuery());
	}

	public function dataGetUriFromGlobals(){

		$server = [
			'REQUEST_URI'     => '/blog/article.php?id=10&user=foo',
			'SERVER_PORT'     => '443',
			'SERVER_ADDR'     => '217.112.82.20',
			'SERVER_NAME'     => 'www.example.org',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'REQUEST_METHOD'  => 'POST',
			'QUERY_STRING'    => 'id=10&user=foo',
			'DOCUMENT_ROOT'   => '/path/to/your/server/root/',
			'HTTP_HOST'       => 'www.example.org',
			'HTTPS'           => 'on',
			'REMOTE_ADDR'     => '193.60.168.69',
			'REMOTE_PORT'     => '5390',
			'SCRIPT_NAME'     => '/blog/article.php',
			'SCRIPT_FILENAME' => '/path/to/your/server/root/blog/article.php',
			'PHP_SELF'        => '/blog/article.php',
			'REQUEST_TIME'    => time(), // phpunit fix
		];

		return [
			'HTTPS request' => [
				'https://www.example.org/blog/article.php?id=10&user=foo',
				$server,
			],
			'HTTPS request with different on value' => [
				'https://www.example.org/blog/article.php?id=10&user=foo',
				array_merge($server, ['HTTPS' => '1']),
			],
			'HTTP request' => [
				'http://www.example.org/blog/article.php?id=10&user=foo',
				array_merge($server, ['HTTPS' => 'off', 'SERVER_PORT' => '80']),
			],
			'HTTP_HOST missing -> fallback to SERVER_NAME' => [
				'https://www.example.org/blog/article.php?id=10&user=foo',
				array_merge($server, ['HTTP_HOST' => null]),
			],
			'HTTP_HOST and SERVER_NAME missing -> fallback to SERVER_ADDR' => [
				'https://217.112.82.20/blog/article.php?id=10&user=foo',
				array_merge($server, ['HTTP_HOST' => null, 'SERVER_NAME' => null]),
			],
			'No query String' => [
				'https://www.example.org/blog/article.php',
				array_merge($server, ['REQUEST_URI' => '/blog/article.php', 'QUERY_STRING' => '']),
			],
			'Host header with port' => [
				'https://www.example.org:8324/blog/article.php?id=10&user=foo',
				array_merge($server, ['HTTP_HOST' => 'www.example.org:8324']),
			],
			'Different port with SERVER_PORT' => [
				'https://www.example.org:8324/blog/article.php?id=10&user=foo',
				array_merge($server, ['SERVER_PORT' => '8324']),
			],
			'REQUEST_URI missing query string' => [
				'https://www.example.org/blog/article.php?id=10&user=foo',
				array_merge($server, ['REQUEST_URI' => '/blog/article.php']),
			],
			'Empty server variable' => [
				'http://localhost',
				['REQUEST_TIME' => time(), 'SCRIPT_NAME' => '/blog/article.php'], // phpunit fix
			],
		];
	}

	/**
	 * @dataProvider dataGetUriFromGlobals
	 *
	 * @param string $expected
	 * @param array  $serverParams
	 */
	public function testGetUriFromGlobals(string $expected, array $serverParams){
		$_SERVER = $serverParams;

		$this->assertEquals(new Uri($expected), Psr17\create_uri_from_globals());
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage user must be a string
	 */
	public function testFilterUserInvalidType(){
		$parts['user'] = [];
		(new Uri)->fromParts($parts);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage pass must be a string
	 */
	public function testFilterPassInvalidType(){
		$parts['pass'] = [];
		(new Uri)->fromParts($parts);
	}

	public function testFilterHostIPv6(){
		$parts['host'] = '::1';
		$uri = Uri::fromParts($parts);

		$this->assertSame('[::1]', $uri->getHost());
	}

	public function testWithPartSamePart(){
		$expected = 'https://example.com/foo#bar';

		$uri = new Uri($expected);

		$uri->withScheme('https');
		$this->assertSame($expected, (string)$uri);

		$uri->withHost('example.com');
		$this->assertSame($expected, (string)$uri);

		$uri->withPath('/foo');
		$this->assertSame($expected, (string)$uri);

		$uri->withFragment('bar');
		$this->assertSame($expected, (string)$uri);
	}

}
