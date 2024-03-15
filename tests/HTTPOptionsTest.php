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

}
