<?php
/**
 * Class UriTest
 *
 * @link https://github.com/guzzle/psr7/blob/c0dcda9f54d145bd4d062a6d15f54931a67732f9/tests/UriTest.php
 * @link https://github.com/bakame-php/psr7-uri-interface-tests/blob/5a556fdfe668a6c6a14772efeba6134c0b7dae34/tests/AbstractUriTestCase.php
 *
 * @created      10.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use InvalidArgumentException;

class UriTest extends TestCase{

	public function testDefaultReturnValuesOfGetters():void{
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

	public function testParsesProvidedUri():void{
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

	public function testCanTransformAndRetrievePartsIndividually():void{

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

	public function testSupportsUrlEncodedValues():void{

		$uri = (new Uri)
			->withScheme('https')
			->withUserInfo('foo\user%3D=', 'pass%3D=')
			->withHost('example.com')
			->withPort(8080)
			->withPath('/path/123')
			->withQuery('q=abc')
			->withFragment('test')
		;

		$this::assertSame('https', $uri->getScheme());
		$this::assertSame('foo\user%3D%3D:pass%3D%3D@example.com:8080', $uri->getAuthority());
		$this::assertSame('foo\user%3D%3D:pass%3D%3D', $uri->getUserInfo());
		$this::assertSame('example.com', $uri->getHost());
		$this::assertSame(8080, $uri->getPort());
		$this::assertSame('/path/123', $uri->getPath());
		$this::assertSame('q=abc', $uri->getQuery());
		$this::assertSame('test', $uri->getFragment());
		$this::assertSame('https://foo\user%3D%3D:pass%3D%3D@example.com:8080/path/123?q=abc#test', (string)$uri);
	}

	public static function getValidUris():array{
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

	#[DataProvider('getValidUris')]
	public function testValidUrisStayValid(string $input):void{
		$this::assertSame($input, (string)(new Uri($input)));
	}

	#[DataProvider('getValidUris')]
	public function testFromParts(string $input):void{
		$this::assertSame($input, (string)(new Uri(parse_url($input))));
	}

	public static function getInvalidUris():array{
		return [
			// parse_url() requires the host component which makes sense for http(s)
			// but not when the scheme is not known or different. So '//' or '///' is
			// currently invalid as well but should not according to RFC 3986.
			'only scheme'     => ['https://'],
			// host cannot contain ":"
			'host with colon' => ['urn://host:with:colon'],
		];
	}

	#[DataProvider('getInvalidUris')]
	public function testInvalidUrisThrowException(string $invalidUri):void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unable to parse URI');

		new Uri($invalidUri);
	}

	public function testPortMustBeValid():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('invalid port: 82517');

		(new Uri)->withPort(82517);
	}

	public function testWithPortCannotBeNegative():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('invalid port: -1');

		(new Uri)->withPort(-1);
	}

	public function testParseUriPortCannotBeNegative():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unable to parse URI');

		new Uri('//example.com:-1');
	}

	public function testParseUriPortCanBeZero(){
		// @see https://bugs.php.net/bug.php?id=80266
		$this::assertSame(0, (new Uri('//example.com:0'))->getPort());
	}

	public function testCanParseFalseyUriParts():void{
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

	public function testCanConstructFalseyUriParts():void{

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

	public function testSchemeIsNormalizedToLowercase():void{
		$uri = new Uri('HTTPS://example.com');

		$this::assertSame('https', $uri->getScheme());
		$this::assertSame('https://example.com', (string)$uri);

		$uri = (new Uri('//example.com'))->withScheme('HTTPS');

		$this::assertSame('https', $uri->getScheme());
		$this::assertSame('https://example.com', (string)$uri);
	}

	public function testHostIsNormalizedToLowercase():void{
		$uri = new Uri('//eXaMpLe.CoM');

		$this::assertSame('example.com', $uri->getHost());
		$this::assertSame('//example.com', (string)$uri);

		$uri = (new Uri)->withHost('eXaMpLe.CoM');

		$this::assertSame('example.com', $uri->getHost());
		$this::assertSame('//example.com', (string)$uri);
	}

	public function testPortIsNullIfStandardPortForScheme():void{
		// HTTPS standard port
		$uri = new Uri('https://example.com:443');

		$this::assertNull($uri->getPort());
		$this::assertSame('example.com', $uri->getAuthority());

		$uri = (new Uri('https://example.com'))->withPort(443);

		$this::assertNull($uri->getPort());
		$this::assertSame('example.com', $uri->getAuthority());

		// HTTP standard port
		$uri = new Uri('https://example.com:443');

		$this::assertNull($uri->getPort());
		$this::assertSame('example.com', $uri->getAuthority());

		$uri = (new Uri('http://example.com'))->withPort(80);

		$this::assertNull($uri->getPort());
		$this::assertSame('example.com', $uri->getAuthority());
	}

	public function testPortIsReturnedIfSchemeUnknown():void{
		$uri = (new Uri('//example.com'))->withPort(80);

		$this::assertSame(80, $uri->getPort());
		$this::assertSame('example.com:80', $uri->getAuthority());
	}

	public function testStandardPortIsNullIfSchemeChanges():void{
		$uri = new Uri('http://example.com:443');

		$this::assertSame('http', $uri->getScheme());
		$this::assertSame(443, $uri->getPort());

		$uri = $uri->withScheme('https');

		$this::assertNull($uri->getPort());
	}

	public function testPortCanBeRemoved():void{
		$uri = (new Uri('https://example.com:8080'))->withPort(null);

		$this::assertNull($uri->getPort());
		$this::assertSame('https://example.com', (string)$uri);
	}

	/**
	 * In RFC 8986 the host is optional and the authority can only
	 * consist of the user info and port.
	 */
	public function testAuthorityWithUserInfoOrPortButWithoutHost():void{
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

	public function testHostInUriDefaultsToLocalhost():void{
		$uri = (new Uri)->withScheme('https');
		// host is empty when requested specifically
		$this::assertSame('', $uri->getHost());
		// "fixed" to localhost
		$this::assertSame('localhost', $uri->getAuthority());
		$this::assertSame('https://localhost', (string)$uri);
	}

	public function testFileSchemeWithEmptyHostReconstruction():void{
		$uri = new Uri('file:///tmp/filename.ext');

		$this::assertSame('', $uri->getHost());
		$this::assertSame('', $uri->getAuthority());
		$this::assertSame('file:///tmp/filename.ext', (string)$uri);
	}

	public static function uriComponentsEncodingProvider():array{
		$unreserved = 'a-zA-Z0-9.-_~!$&\'()*+,;=:@';

		return [
			'Percent encode spaces'                            => [
				'/pa th?q=va lue#frag ment',
				'/pa%20th',
				'q=va%20lue',
				'frag%20ment',
				'/pa%20th?q=va%20lue#frag%20ment',
			],
			'Percent encode multibyte'                         => [
				'/€?€#€',
				'/%E2%82%AC',
				'%E2%82%AC',
				'%E2%82%AC',
				'/%E2%82%AC?%E2%82%AC#%E2%82%AC',
			],
			'Don\'t encode already encoded'                    => [
				'/pa%20th?q=va%20lue#frag%20ment',
				'/pa%20th',
				'q=va%20lue',
				'frag%20ment',
				'/pa%20th?q=va%20lue#frag%20ment',
			],
			'Percent encode invalid percent encodings'         => [
				'/pa%2-th?q=va%2-lue#frag%2-ment',
				'/pa%252-th',
				'q=va%252-lue',
				'frag%252-ment',
				'/pa%252-th?q=va%252-lue#frag%252-ment',
			],
			'Don\'t encode path segments'                      => [
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
			'Encoded unreserved chars are not decoded'         => [
				'/p%61th?q=v%61lue#fr%61gment',
				'/p%61th',
				'q=v%61lue',
				'fr%61gment',
				'/p%61th?q=v%61lue#fr%61gment',
			],
		];
	}

	#[DataProvider('uriComponentsEncodingProvider')]
	public function testUriComponentsGetEncodedProperly(
		string $input,
		string $path,
		string $query,
		string $fragment,
		string $output
	):void{
		$uri = new Uri($input);

		$this::assertSame($path, $uri->getPath());
		$this::assertSame($query, $uri->getQuery());
		$this::assertSame($fragment, $uri->getFragment());
		$this::assertSame($output, (string)$uri);
	}

	public function testWithPathEncodesProperly():void{
		$uri = (new Uri)->withPath('/baz?#€/b%61r');
		// Query and fragment delimiters and multibyte chars are encoded.
		$this::assertSame('/baz%3F%23%E2%82%AC/b%61r', $uri->getPath());
		$this::assertSame('/baz%3F%23%E2%82%AC/b%61r', (string)$uri);
	}

	public function testWithQueryEncodesProperly():void{
		$uri = (new Uri)->withQuery('?=#&€=/&b%61r');
		// A query starting with a "?" is valid and must not be magically removed. Otherwise, it would be impossible to
		// construct such a URI. Also, the "?" and "/" does not need to be encoded in the query.
		$this::assertSame('?=%23&%E2%82%AC=/&b%61r', $uri->getQuery());
		$this::assertSame('??=%23&%E2%82%AC=/&b%61r', (string)$uri);
	}

	public function testWithFragmentEncodesProperly():void{
		$uri = (new Uri)->withFragment('#€?/b%61r');
		// A fragment starting with a "#" is valid and must not be magically removed. Otherwise, it would be impossible to
		// construct such a URI. Also, the "?" and "/" does not need to be encoded in the fragment.
		$this::assertSame('%23%E2%82%AC?/b%61r', $uri->getFragment());
		$this::assertSame('#%23%E2%82%AC?/b%61r', (string)$uri);
	}

	public function testAllowsForRelativeUri():void{
		$uri = (new Uri)->withPath('foo');

		$this::assertSame('foo', $uri->getPath());
		$this::assertSame('foo', (string)$uri);
	}

	public function testPathStartingWithTwoSlashes():void{
		$uri = new Uri('https://example.org//path-not-host.com');

		$this::assertSame('//path-not-host.com', $uri->getPath());

		$uri = $uri->withScheme('');

		$this::assertSame('//example.org//path-not-host.com', (string)$uri); // This is still valid

		$uri = $uri->withHost('');
		// we're not going to "fix" this case here as the path is requested explicitly - deal with it
		$this::assertSame('//path-not-host.com', $uri->getPath());
		// URI "//path-not-host.com" would be interpreted as network reference and thus change the original path to the host
		$this::assertSame('/path-not-host.com', (string)$uri);
	}

	public function testRelativeUriWithPathBeginningWithColonSegmentIsInvalid():void{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('A relative URI must not have a path beginning with a segment containing a colon');

		(string)((new Uri)->withPath('mailto:foo'));
	}

	public function testRelativeUriWithPathHavingColonSegment():void{
		$uri = (new Uri('urn:/mailto:foo'))->withScheme('');
		$this::assertSame('/mailto:foo', $uri->getPath());

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('A relative URI must not have a path beginning with a segment containing a colon');

		(string)((new Uri('urn:mailto:foo'))->withScheme(''));
	}

	public function testAddsSlashForRelativeUriStringWithHost():void{
		// If the path is rootless and an authority is present, the path MUST be prefixed by "/".
		$uri = (new Uri)->withPath('foo')->withHost('example.com');

		$this::assertSame('foo', $uri->getPath()); // path alone is not fixed as per interface spec
		// concatenating a relative path with a host doesn't work: "//example.comfoo" would be wrong
		$this::assertSame('//example.com/foo', (string)$uri);
	}

	public static function hostProvider():array{
		return [
			'normalized host' => ['MaStEr.eXaMpLe.CoM', 'master.example.com'],
			'simple host'     => ['www.example.com', 'www.example.com'],
			'IPv6 Host'       => ['[::1]', '[::1]'],
		];
	}

	/**
	 * The value returned MUST be normalized to lowercase, per RFC 3986 Section 3.2.2.
	 */
	#[DataProvider('hostProvider')]
	public function testGetHost(string $host, string $expected):void{
		$uri = (new Uri)->withHost($host);

		$this::assertInstanceOf(UriInterface::class, $uri);
		$this::assertSame($expected, $uri->getHost(), 'Host must be normalized according to RFC3986');
	}

	public static function authorityProvider():array{
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
	 * If the port component is not set or is the standard port for the current scheme, it SHOULD NOT be included.
	 */
	#[DataProvider('authorityProvider')]
	public function testGetAuthority(string $scheme, string $user, string $pass, string $host, ?int $port, string $authority):void{

		$uri = (new Uri)
			->withHost($host)
			->withScheme($scheme)
			->withUserInfo($user, $pass)
			->withPort($port)
		;

		$this::assertSame($authority, $uri->getAuthority());
	}

	public function testFilterHostIPv6():void{
		$this::assertSame('[::1]', (new Uri(['host' => '::1']))->getHost());
		$this::assertSame('[::1]', (new Uri(['host' => '[::1]']))->getHost());
	}

	public function testWithPartSamePart():void{
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

	public function testInternationalizedDomainName():void{
		$uri = new Uri('https://яндекс.рф');

		$this::assertSame('яндекс.рф', $uri->getHost());

		$uri = new Uri('https://яндекAс.рф');

		$this::assertSame('яндекaс.рф', $uri->getHost());
	}

	public function testIPv6Host():void{
		$uri = new Uri('https://[2a00:f48:1008::212:183:10]');

		$this::assertSame('[2a00:f48:1008::212:183:10]', $uri->getHost());

		$uri = new Uri('https://[2a00:f48:1008::212:183:10]:56?foo=bar');

		$this::assertSame('[2a00:f48:1008::212:183:10]', $uri->getHost());
		$this::assertSame(56, $uri->getPort());
		$this::assertSame('foo=bar', $uri->getQuery());
	}

}
