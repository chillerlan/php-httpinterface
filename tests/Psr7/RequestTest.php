<?php
/**
 * Class RequestTest
 *
 * @link https://github.com/guzzle/psr7/blob/4b981cdeb8c13d22a6c193554f8c686f53d5c958/tests/RequestTest.php
 *
 * @filesource   RequestTest.php
 * @created      12.08.2018
 * @package      chillerlan\HTTPTest\Psr7
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7\{Request, Uri};
use chillerlan\HTTP\Psr17\RequestFactory;
use Psr\Http\Message\StreamInterface;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase{

	/**
	 * @var \chillerlan\HTTP\Psr17\RequestFactory
	 */
	protected $requestFactory;

	protected function setUp(){
		$this->requestFactory = new RequestFactory;
	}

	public function testRequestUriMayBeString(){
		$this->assertEquals('/', $this->requestFactory->createRequest($this->requestFactory::METHOD_GET, '/')->getUri()); // RequestFactory coverage
	}

	public function testRequestUriMayBeUri(){
		$uri = new Uri('/');

		$this->assertSame($uri, (new Request('GET', $uri))->getUri());
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testValidateRequestUri(){
		new Request('GET', '///');
	}

	public function testCanConstructWithBody(){
		$r = new Request('GET', '/', [], 'baz');

		$this->assertInstanceOf(StreamInterface::class, $r->getBody());
		$this->assertEquals('baz', (string)$r->getBody());
	}

	public function testNullBody(){
		$r = new Request('GET', '/', [], null);

		$this->assertInstanceOf(StreamInterface::class, $r->getBody());
		$this->assertSame('', (string)$r->getBody());
	}

	public function testFalseyBody(){
		$r = new Request('GET', '/', [], '0');

		$this->assertInstanceOf(StreamInterface::class, $r->getBody());
		$this->assertSame('0', (string)$r->getBody());
	}

	public function testConstructorDoesNotReadStreamBody(){
		$body = $this->getMockBuilder(StreamInterface::class)->getMock();
		$body->expects($this->never())->method('__toString');

		$this->assertSame($body, (new Request('GET', '/', [], $body))->getBody());
	}

	public function testCapitalizesMethod(){
		$this->assertEquals('GET', (new Request('get', '/'))->getMethod());
	}

	public function testCapitalizesWithMethod(){
		$this->assertEquals('PUT', (new Request('GET', '/'))->withMethod('put')->getMethod());
	}

	public function testWithUri(){
		$r1 = new Request('GET', '/');
		$u1 = $r1->getUri();

		$u2 = new Uri('http://www.example.com');
		$r2 = $r1->withUri($u2);

		$this->assertNotSame($r1, $r2);
		$this->assertSame($u2, $r2->getUri());
		$this->assertSame($u1, $r1->getUri());
	}

	public function testSameInstanceWhenSameUri(){
		$r1 = new Request('GET', 'http://foo.com');
		$r2 = $r1->withUri($r1->getUri());

		$this->assertSame($r1, $r2);
	}

	public function testWithRequestTarget(){
		$r1 = new Request('GET', '/');
		$r2 = $r1->withRequestTarget('*');

		$this->assertEquals('*', $r2->getRequestTarget());
		$this->assertEquals('/', $r1->getRequestTarget());
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testRequestTargetDoesNotAllowSpaces(){
		(new Request('GET', '/'))->withRequestTarget('/foo bar');
	}

	public function testRequestTargetDefaultsToSlash(){
		$r1 = new Request('GET', '');
		$this->assertEquals('/', $r1->getRequestTarget());

		$r2 = new Request('GET', '*');
		$this->assertEquals('*', $r2->getRequestTarget());

		$r3 = new Request('GET', 'http://foo.com/bar baz/');
		$this->assertEquals('/bar%20baz/', $r3->getRequestTarget());
	}

	public function testBuildsRequestTarget(){
		$this->assertEquals('/baz?bar=bam', (new Request('GET', 'http://foo.com/baz?bar=bam'))->getRequestTarget());
	}

	public function testBuildsRequestTargetWithFalseyQuery(){
		$this->assertEquals('/baz?0', (new Request('GET', 'http://foo.com/baz?0'))->getRequestTarget());
	}

	public function testHostIsAddedFirst(){
		$r = new Request('GET', 'http://foo.com/baz?bar=bam', ['Foo' => 'Bar']);

		$this->assertEquals(['Host' => ['foo.com'], 'Foo'  => ['Bar']], $r->getHeaders());
	}

	public function testCanGetHeaderAsCsv(){
		$r = new Request('GET', 'http://foo.com/baz?bar=bam', ['Foo' => ['a', 'b', 'c']]);

		$this->assertEquals('a, b, c', $r->getHeaderLine('Foo'));
		$this->assertEquals('', $r->getHeaderLine('Bar'));
	}

	public function testHostIsNotOverwrittenWhenPreservingHost(){
		$r1 = new Request('GET', 'http://foo.com/baz?bar=bam', ['Host' => 'a.com']);
		$this->assertEquals(['Host' => ['a.com']], $r1->getHeaders());

		$r2 = $r1->withUri(new Uri('http://www.foo.com/bar'), true);
		$this->assertEquals('a.com', $r2->getHeaderLine('Host'));
	}

	public function testOverridesHostWithUri(){
		$r1 = new Request('GET', 'http://foo.com/baz?bar=bam');
		$this->assertEquals(['Host' => ['foo.com']], $r1->getHeaders());

		$r2 = $r1->withUri(new Uri('http://www.baz.com/bar'));
		$this->assertEquals('www.baz.com', $r2->getHeaderLine('Host'));
	}

	public function testAggregatesHeaders(){
		$r = new Request('GET', '', ['ZOO' => 'zoobar', 'zoo' => ['foobar', 'zoobar']]);

		$this->assertEquals(['ZOO' => ['zoobar', 'foobar', 'zoobar']], $r->getHeaders());
		$this->assertEquals('zoobar, foobar, zoobar', $r->getHeaderLine('zoo'));
	}

	public function testSupportNumericHeaders(){
		$r = new Request('GET', '', ['Content-Length' => 200]);

		$this->assertSame(['Content-Length' => ['200']], $r->getHeaders());
		$this->assertSame('200', $r->getHeaderLine('Content-Length'));
	}

	public function testAddsPortToHeader(){
		$this->assertEquals('foo.com:8124', (new Request('GET', 'http://foo.com:8124/bar'))->getHeaderLine('host'));
	}

	public function testAddsPortToHeaderAndReplacePreviousPort(){
		$r = (new Request('GET', 'http://foo.com:8124/bar'))
			->withUri(new Uri('http://foo.com:8125/bar'));

		$this->assertEquals('foo.com:8125', $r->getHeaderLine('host'));
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Method must be a string
	 */
	public function testWithMethodInvalidMethod(){
		(new Request('GET', '/foo'))->withMethod([]);
	}

	public function headerDataProvider():array {
		return [
			[['content-Type' => 'application/x-www-form-urlencoded'], ['Content-type' => 'Content-type: application/x-www-form-urlencoded']],
			[['lowercasekey' => 'lowercasevalue'], ['Lowercasekey' => 'Lowercasekey: lowercasevalue']],
			[['UPPERCASEKEY' => 'UPPERCASEVALUE'], ['Uppercasekey' => 'Uppercasekey: UPPERCASEVALUE']],
			[['mIxEdCaSeKey' => 'MiXeDcAsEvAlUe'], ['Mixedcasekey' => 'Mixedcasekey: MiXeDcAsEvAlUe']],
			[['31i71casekey' => '31i71casevalue'], ['31i71casekey' => '31i71casekey: 31i71casevalue']],
			[[1 => 'numericvalue:1'], ['Numericvalue'  => 'Numericvalue: 1']],
			[[2 => 2], []],
			[['what'], []],
		];
	}

	/**
	 * @dataProvider headerDataProvider
	 *
	 * @param array $header
	 * @param array $normalized
	 */
	public function testNormalizeHeaders(array $header, array $normalized){
		$this->assertSame($normalized, $this->requestFactory->normalizeRequestHeaders($header));
	}

}
