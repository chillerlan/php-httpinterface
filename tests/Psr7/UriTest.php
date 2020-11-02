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

use chillerlan\HTTP\Psr7\{Uri, UriExtended};
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use PHPUnit\Framework\TestCase;

class UriTest extends TestCase{

	public function testParsesProvidedUri(){
		$uri = new Uri('https://user:pass@example.com:8080/path/123?q=abc#test');

		$this::assertSame('https', $uri->getScheme());
		$this::assertSame('user:pass@example.com:8080', $uri->getAuthority());
		$this::assertSame('user:pass', $uri->getUserInfo());
		$this::assertSame('example.com', $uri->getHost());
		$this::assertSame(8080, $uri->getPort());
		$this::assertSame('/path/123', $uri->getPath());
		$this::assertSame('q=abc', $uri->getQuery());
		$this::assertSame('test', $uri->getFragment());
		$this::assertSame('https://user:pass@example.com:8080/path/123?q=abc#test', (string)$uri);
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

		$this::assertSame('https', $uri->getScheme());
		$this::assertSame('user:pass@example.com:8080', $uri->getAuthority());
		$this::assertSame('user:pass', $uri->getUserInfo());
		$this::assertSame('example.com', $uri->getHost());
		$this::assertSame(8080, $uri->getPort());
		$this::assertSame('/path/123', $uri->getPath());
		$this::assertSame('q=abc', $uri->getQuery());
		$this::assertSame('test', $uri->getFragment());
		$this::assertSame('https://user:pass@example.com:8080/path/123?q=abc#test', (string)$uri);
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
	 *
	 * @param $input
	 */
	public function testValidUrisStayValid($input){
		$this::assertSame($input, (string)(new Uri($input)));
	}

	/**
	 * @dataProvider getValidUris
	 *
	 * @param $input
	 */
	public function testFromParts($input){
		$this::assertSame($input, (string)UriExtended::fromParts(parse_url($input)));
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
	 * @dataProvider getInvalidUris
	 *
	 * @param $invalidUri
	 */
	public function testInvalidUrisThrowException($invalidUri){
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('invalid URI');

		new Uri($invalidUri);
	}

	public function testPortMustBeValid(){
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('invalid port: 82517');

		(new Uri)->withPort(82517);
	}

	public function testWithPortCannotBeZero(){
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('invalid port: 0');

		(new Uri)->withPort(0);
	}

	public function testParseUriPortCannotBeZero(){

		if(\PHP_MAJOR_VERSION > 7){
			$this::markTestSkipped('https://bugs.php.net/bug.php?id=80266');
			return;
		}

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('invalid URI: "//example.com:0');

		new Uri('//example.com:0');
	}

	public function testSchemeMustHaveCorrectType(){
		$this->expectException(InvalidArgumentException::class);

		(new Uri)->withScheme([]);
	}

	public function testHostMustHaveCorrectType(){
		$this->expectException(InvalidArgumentException::class);

		(new Uri)->withHost([]);
	}

	public function testPathMustHaveCorrectType(){
		$this->expectException(InvalidArgumentException::class);

		(new Uri)->withPath([]);
	}

	public function testQueryMustHaveCorrectType(){
		$this->expectException(InvalidArgumentException::class);

		(new Uri)->withQuery([]);
	}

	public function testFragmentMustHaveCorrectType(){
		$this->expectException(InvalidArgumentException::class);

		(new Uri)->withFragment([]);
	}

	public function testCanParseFalseyUriParts(){
		$uri = new Uri('0://0:0@0/0?0#0');

		$this::assertSame('0', $uri->getScheme());
		$this::assertSame('0:0@0', $uri->getAuthority());
		$this::assertSame('0:0', $uri->getUserInfo());
		$this::assertSame('0', $uri->getHost());
		$this::assertSame('/0', $uri->getPath());
		$this::assertSame('0', $uri->getQuery());
		$this::assertSame('0', $uri->getFragment());
		$this::assertSame('0://0:0@0/0?0#0', (string)$uri);
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

		$this::assertSame('0', $uri->getScheme());
		$this::assertSame('0:0@0', $uri->getAuthority());
		$this::assertSame('0:0', $uri->getUserInfo());
		$this::assertSame('0', $uri->getHost());
		$this::assertSame('/0', $uri->getPath());
		$this::assertSame('0', $uri->getQuery());
		$this::assertSame('0', $uri->getFragment());
		$this::assertSame('0://0:0@0/0?0#0', (string)$uri);
	}

	public function testSchemeIsNormalizedToLowercase(){
		$uri = new Uri('HTTP://example.com');

		$this::assertSame('http', $uri->getScheme());
		$this::assertSame('http://example.com', (string)$uri);

		$uri = (new Uri('//example.com'))->withScheme('HTTP');

		$this::assertSame('http', $uri->getScheme());
		$this::assertSame('http://example.com', (string)$uri);
	}

	public function testHostIsNormalizedToLowercase(){
		$uri = new Uri('//eXaMpLe.CoM');

		$this::assertSame('example.com', $uri->getHost());
		$this::assertSame('//example.com', (string)$uri);

		$uri = (new Uri)->withHost('eXaMpLe.CoM');

		$this::assertSame('example.com', $uri->getHost());
		$this::assertSame('//example.com', (string)$uri);
	}

	public function testPortIsNullIfStandardPortForScheme(){
		// HTTPS standard port
		$uri = new Uri('https://example.com:443');
		$this::assertNull($uri->getPort());
		$this::assertSame('example.com', $uri->getAuthority());

		$uri = (new Uri('https://example.com'))->withPort(443);
		$this::assertNull($uri->getPort());
		$this::assertSame('example.com', $uri->getAuthority());

		// HTTP standard port
		$uri = new Uri('http://example.com:80');
		$this::assertNull($uri->getPort());
		$this::assertSame('example.com', $uri->getAuthority());

		$uri = (new Uri('http://example.com'))->withPort(80);
		$this::assertNull($uri->getPort());
		$this::assertSame('example.com', $uri->getAuthority());
	}

	public function testPortIsReturnedIfSchemeUnknown(){
		$uri = (new Uri('//example.com'))->withPort(80);

		$this::assertSame(80, $uri->getPort());
		$this::assertSame('example.com:80', $uri->getAuthority());
	}

	public function testStandardPortIsNullIfSchemeChanges(){
		$uri = new Uri('http://example.com:443');
		$this::assertSame('http', $uri->getScheme());
		$this::assertSame(443, $uri->getPort());

		$uri = $uri->withScheme('https');
		$this::assertNull($uri->getPort());
	}

	public function testPortPassedAsStringIsCastedToInt(){
		$uri = (new Uri('//example.com'))->withPort('8080');

		$this::assertSame(8080, $uri->getPort(), 'Port is returned as integer');
		$this::assertSame('example.com:8080', $uri->getAuthority());
	}

	public function testPortCanBeRemoved(){
		$uri = (new Uri('http://example.com:8080'))->withPort(null);

		$this::assertNull($uri->getPort());
		$this::assertSame('http://example.com', (string)$uri);
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
	 *
	 * @param $input
	 * @param $path
	 * @param $query
	 * @param $fragment
	 * @param $output
	 */
	public function testUriComponentsGetEncodedProperly($input, $path, $query, $fragment, $output){
		$uri = new Uri($input);

		$this::assertSame($path, $uri->getPath());
		$this::assertSame($query, $uri->getQuery());
		$this::assertSame($fragment, $uri->getFragment());
		$this::assertSame($output, (string)$uri);
	}

	/**
	 * In RFC 8986 the host is optional and the authority can only
	 * consist of the user info and port.
	 */
	public function testAuthorityWithUserInfoOrPortButWithoutHost(){
		$uri = (new Uri)->withUserInfo('user', 'pass');

		$this::assertSame('user:pass', $uri->getUserInfo());
		$this::assertSame('user:pass@', $uri->getAuthority());

		$uri = $uri->withPort(8080);
		$this::assertSame(8080, $uri->getPort());
		$this::assertSame('user:pass@:8080', $uri->getAuthority());
		$this::assertSame('//user:pass@:8080', (string)$uri);

		$uri = $uri->withUserInfo('');
		$this::assertSame(':8080', $uri->getAuthority());
	}

	public function testHostInHttpUriDefaultsToLocalhost(){
		$uri = (new Uri)->withScheme('http');

		$this::assertSame('localhost', $uri->getHost());
		$this::assertSame('localhost', $uri->getAuthority());
		$this::assertSame('http://localhost', (string)$uri);
	}

	public function testHostInHttpsUriDefaultsToLocalhost(){
		$uri = (new Uri)->withScheme('https');

		$this::assertSame('localhost', $uri->getHost());
		$this::assertSame('localhost', $uri->getAuthority());
		$this::assertSame('https://localhost', (string)$uri);
	}

	public function testFileSchemeWithEmptyHostReconstruction(){
		$uri = new Uri('file:///tmp/filename.ext');

		$this::assertSame('', $uri->getHost());
		$this::assertSame('', $uri->getAuthority());
		$this::assertSame('file:///tmp/filename.ext', (string)$uri);
	}

	public function testWithPathEncodesProperly(){
		$uri = (new Uri)->withPath('/baz?#€/b%61r');
		// Query and fragment delimiters and multibyte chars are encoded.
		$this::assertSame('/baz%3F%23%E2%82%AC/b%61r', $uri->getPath());
		$this::assertSame('/baz%3F%23%E2%82%AC/b%61r', (string)$uri);
	}

	public function testWithQueryEncodesProperly(){
		$uri = (new Uri)->withQuery('?=#&€=/&b%61r');
		// A query starting with a "?" is valid and must not be magically removed. Otherwise it would be impossible to
		// construct such an URI. Also the "?" and "/" does not need to be encoded in the query.
		$this::assertSame('?=%23&%E2%82%AC=/&b%61r', $uri->getQuery());
		$this::assertSame('??=%23&%E2%82%AC=/&b%61r', (string)$uri);
	}

	public function testWithFragmentEncodesProperly(){
		$uri = (new Uri)->withFragment('#€?/b%61r');
		// A fragment starting with a "#" is valid and must not be magically removed. Otherwise it would be impossible to
		// construct such an URI. Also the "?" and "/" does not need to be encoded in the fragment.
		$this::assertSame('%23%E2%82%AC?/b%61r', $uri->getFragment());
		$this::assertSame('#%23%E2%82%AC?/b%61r', (string)$uri);
	}

	public function testAllowsForRelativeUri(){
		$uri = (new Uri)->withPath('foo');

		$this::assertSame('foo', $uri->getPath());
		$this::assertSame('foo', (string)$uri);
	}

	public function testRelativePathAndAuhorityIsAutomagicallyFixed(){
		// concatenating a relative path with a host doesn't work: "//example.comfoo" would be wrong
		$uri = (new Uri)->withPath('foo')->withHost('example.com');

		$this::assertSame('/foo', $uri->getPath());
		$this::assertSame('//example.com/foo', (string)$uri);
	}

	public function testPathStartingWithTwoSlashesAndNoAuthorityIsInvalid(){
		// URI "//foo" would be interpreted as network reference and thus change the original path to the host
		$this::assertSame('/foo', (string)(new Uri)->withPath('//foo'));
	}

	public function testPathStartingWithTwoSlashes(){
		$uri = new Uri('http://example.org//path-not-host.com');
		$this::assertSame('//path-not-host.com', $uri->getPath());

		$uri = $uri->withScheme('');
		$this::assertSame('//example.org//path-not-host.com', (string)$uri); // This is still valid

		$uri = $uri->withHost(''); // Now it becomes invalid
		$this::assertSame('/path-not-host.com', $uri->getPath());
	}

	public function testRelativeUriWithPathBeginngWithColonSegmentIsInvalid(){
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('A relative URI must not have a path beginning with a segment containing a colon');

		(new Uri)->withPath('mailto:foo');
	}

	public function testRelativeUriWithPathHavingColonSegment(){
		$this::assertSame('/mailto:foo', (new Uri('urn:/mailto:foo'))->withScheme('')->getPath());

		$this->expectException(InvalidArgumentException::class);
		(new Uri('urn:mailto:foo'))->withScheme('');
	}

	public function testDefaultReturnValuesOfGetters(){
		$uri = new Uri;

		$this::assertSame('', $uri->getScheme());
		$this::assertSame('', $uri->getAuthority());
		$this::assertSame('', $uri->getUserInfo());
		$this::assertSame('', $uri->getHost());
		$this::assertNull($uri->getPort());
		$this::assertSame('', $uri->getPath());
		$this::assertSame('', $uri->getQuery());
		$this::assertSame('', $uri->getFragment());
	}

	public function testImmutability(){
		$uri = new Uri;

		$this::assertNotSame($uri, $uri->withScheme('https'));
		$this::assertNotSame($uri, $uri->withUserInfo('user', 'pass'));
		$this::assertNotSame($uri, $uri->withHost('example.com'));
		$this::assertNotSame($uri, $uri->withPort(8080));
		$this::assertNotSame($uri, $uri->withPath('/path/123'));
		$this::assertNotSame($uri, $uri->withQuery('q=abc'));
		$this::assertNotSame($uri, $uri->withFragment('test'));
	}

	public function testAddsSlashForRelativeUriStringWithHost(){
		// If the path is rootless and an authority is present, the path MUST
		// be prefixed by "/".
		$uri = (new Uri)->withPath('foo')->withHost('example.com');

		$this::assertSame('/foo', $uri->getPath());
		// concatenating a relative path with a host doesn't work: "//example.comfoo" would be wrong
		$this::assertSame('//example.com/foo', (string)$uri);
	}

	public function testRemoveExtraSlashesWihoutHost(){
		// If the path is starting with more than one "/" and no authority is
		// present, the starting slashes MUST be reduced to one.
		$uri = (new Uri)->withPath('//foo');

		$this::assertSame('/foo', $uri->getPath());
		// URI "//foo" would be interpreted as network reference and thus change the original path to the host
		$this::assertSame('/foo', (string)$uri);
	}

	public function hostProvider(){
		return [
			'normalized host' => ['MaStEr.eXaMpLe.CoM', 'master.example.com',],
			'simple host'     => ['www.example.com', 'www.example.com',],
			'IPv6 Host'       => ['[::1]', '[::1]'],
		];
	}

	/**
	 * The value returned MUST be normalized to lowercase, per RFC 3986
	 * Section 3.2.2.
	 *
	 * @dataProvider hostProvider
	 *
	 * @param $host
	 * @param $expected
	 */
	public function testGetHost($host, $expected){
		$uri = (new Uri)->withHost($host);

		$this::assertInstanceOf(UriInterface::class, $uri);
		$this::assertSame($expected, $uri->getHost(), 'Host must be normalized according to RFC3986');
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
			'authority without pass'              => [
				'scheme'    => 'http',
				'user'      => 'User',
				'pass'      => '',
				'host'      => 'master.example.com',
				'port'      => null,
				'authority' => 'User@master.example.com',
			],
			'authority without port and userinfo' => [
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

		$this::assertSame($authority, $uri->getAuthority());
	}


	public function testFilterUserInvalidType(){
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('user must be a string');

		$parts['user'] = [];
		UriExtended::fromParts($parts);
	}

	public function testFilterPassInvalidType(){
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('pass must be a string');

		$parts['pass'] = [];
		UriExtended::fromParts($parts);
	}

	public function testFilterHostIPv6(){
		$parts['host'] = '::1';
		$uri = UriExtended::fromParts($parts);

		$this::assertSame('[::1]', $uri->getHost());
	}

	public function testWithPartSamePart(){
		$expected = 'https://example.com/foo#bar';

		$uri = new Uri($expected);

		$uri->withScheme('https');
		$this::assertSame($expected, (string)$uri);

		$uri->withHost('example.com');
		$this::assertSame($expected, (string)$uri);

		$uri->withPath('/foo');
		$this::assertSame($expected, (string)$uri);

		$uri->withFragment('bar');
		$this::assertSame($expected, (string)$uri);
	}

}
