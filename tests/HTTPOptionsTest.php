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

final class HTTPOptionsTest extends TestCase{

	public function testInvalidUserAgentException():void{
		$this->expectException(ClientExceptionInterface::class);
		$this->expectExceptionMessageIs('invalid user agent');
		/** @phan-suppress-next-line PhanNoopNew */
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
		$this->expectExceptionMessageIsOrContains('invalid DNS-over-HTTPS URL');
		/** @phan-suppress-next-line PhanNoopNew */
		new HTTPOptions(['dns_over_https' => 'http://nope.whatever']);
	}

}
