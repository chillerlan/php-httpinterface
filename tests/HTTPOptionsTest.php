<?php
/**
 * Class HTTPOptionsTest
 *
 * @created      14.11.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\HTTPOptions;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;

/**
 *
 */
final class HTTPOptionsTest extends TestCase{

	public function testInvalidUserAgentException():void{
		$this->expectException(ClientExceptionInterface::class);
		$this->expectExceptionMessage('invalid user agent');

		new HTTPOptions(['user_agent' => '']);
	}

	public function testSetDnsOverHttpsURL():void{
		$url = 'https://example.com';

		$options = new HTTPOptions(['dns_over_https' => $url]);
		$this::assertSame($url, $options->dns_over_https);

		// unset
		$options->dns_over_https = null;
		$this::assertNull($options->dns_over_https);

		// via magic
		$options->dns_over_https = $url;
		$this::assertSame($url, $options->dns_over_https);
	}

	public function testSetDnsOverHttpsURLException():void{
		$this->expectException(ClientExceptionInterface::class);
		$this->expectExceptionMessage('invalid DNS-over-HTTPS URL');

		new HTTPOptions(['dns_over_https' => 'http://nope.whatever']);
	}
}
