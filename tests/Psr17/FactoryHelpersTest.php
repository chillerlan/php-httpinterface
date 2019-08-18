<?php
/**
 * Class FactoryHelpersTest
 *
 * @filesource   FactoryHelpersTest.php
 * @created      31.01.2019
 * @package      chillerlan\HTTPTest\Psr17
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr17;

use chillerlan\HTTP\Psr7\{UploadedFile, UriExtended};
use InvalidArgumentException, stdClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

use function chillerlan\HTTP\Psr17\{
	create_uri_from_globals, create_server_request_from_globals, create_stream, create_stream_from_input
};

class FactoryHelpersTest extends TestCase{

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
	public function testCreateUriFromGlobals(string $expected, array $serverParams){
		$_SERVER = $serverParams;

		$this->assertEquals(new UriExtended($expected), create_uri_from_globals());
	}

	public function testCreateServerRequestFromGlobals(){

		$_SERVER = [
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

		$_COOKIE = [
			'logged-in' => 'yes!'
		];

		$_POST = [
			'name'  => 'Pesho',
			'email' => 'pesho@example.com',
		];

		$_GET = [
			'id' => 10,
			'user' => 'foo',
		];

		$_FILES = [
			'file' => [
				'name'     => 'MyFile.txt',
				'type'     => 'text/plain',
				'tmp_name' => '/tmp/php/php1h4j1o',
				'error'    => UPLOAD_ERR_OK,
				'size'     => 123,
			]
		];

		$server = create_server_request_from_globals();

		$this->assertSame('POST', $server->getMethod());
		$this->assertEquals(['Host' => ['www.example.org']], $server->getHeaders());
		$this->assertSame('', (string) $server->getBody());
		$this->assertSame('1.1', $server->getProtocolVersion());
		$this->assertEquals($_COOKIE, $server->getCookieParams());
		$this->assertEquals($_POST, $server->getParsedBody());
		$this->assertEquals($_GET, $server->getQueryParams());

		$this->assertEquals(
			new UriExtended('https://www.example.org/blog/article.php?id=10&user=foo'),
			$server->getUri()
		);

		$expectedFiles = [
			'file' => new UploadedFile(
				'/tmp/php/php1h4j1o',
				123,
				UPLOAD_ERR_OK,
				'MyFile.txt',
				'text/plain'
			),
		];

		$this->assertEquals($expectedFiles, $server->getUploadedFiles());
	}

	public function testCreateStream(){
		$stream = create_stream('test');

		$this->assertInstanceOf(Streaminterface::class, $stream);
		$this->assertSame('test', $stream->getContents());
	}

	public function testCreateStreamInvalidModeException(){
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('invalid mode');

		create_stream('test', 'foo');
	}

	public function streamInputProvider(){

		$fh = fopen('php://temp', 'r+');
		fwrite($fh, 'resourcetest');
		fseek($fh, 0);

		$xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><root><foo>bar</foo></root>');

		return [
			'string'          => ['stringtest', 'stringtest'],
#			'file'            => [__DIR__.'/streaminput.txt', 'filetest'.PHP_EOL],
			'resource'        => [$fh, 'resourcetest'],
			'streaminterface' => [create_stream('streaminterfacetest'), 'streaminterfacetest'],
			'tostring'        => [$xml->foo, 'bar'],
		];
	}

	/**
	 * @dataProvider streamInputProvider
	 *
	 * @param        $input
	 * @param string $content
	 */
	public function testCreateStreamFromInput($input, string $content){
		$this->assertSame($content, create_stream_from_input($input)->getContents());
	}

	public function testCreateStreamFromInputException(){
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid resource type: object');

		create_stream_from_input(new stdClass);
	}

}
