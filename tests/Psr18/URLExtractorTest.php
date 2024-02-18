<?php
/**
 * Class URLExtractorTest
 *
 * @created      15.08.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTPTest\Psr18;

use chillerlan\HTTP\Psr18\{CurlClient, URLExtractor};
use chillerlan\HTTP\Psr7\Request;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Client\ClientInterface;
use function defined;
use const CURLOPT_FOLLOWLOCATION, CURLOPT_MAXREDIRS;

/**
 * @property \chillerlan\HTTP\Psr18\URLExtractor $http
 */
#[Group('slow')]
class URLExtractorTest extends HTTPClientTestAbstract{

	protected function initClient():ClientInterface{
		$this->options->curl_options = [
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_MAXREDIRS      => 25,
		];

		return new URLExtractor(new CurlClient($this->options));
	}

	public function testSendRequest():void{

		/** @noinspection PhpUndefinedConstantInspection */
		if(defined('TEST_IS_CI') && TEST_IS_CI === true){
			$this->markTestSkipped('i have no idea why the headers are empty on travis');
		}

		// reminder: twitter does not delete shortened URLs of deleted tweets (this one was deleted in 2016)
		$this->http->sendRequest(new Request('GET', 'https://t.co/ZSS6nVOcVp'));

		$expected = [
			'https://bit.ly/1oesmr8',
			'http://tinyurl.com/jvc5y98',
			// interesting, this is a new one
			'https://redirect.viglink.com?u=https%3A%2F%2Fapi.guildwars2.com%2Fv2%2Fbuild&key=a7e37b5f6ff1de9cb410158b1013e54a&prodOvrd=RAC&opt=false',
			'https://api.guildwars2.com/v2/build',
			'',
		];

		$responses = $this->http->getResponses();

		$this::assertCount(5, $responses);

		foreach($responses as $i => $r){
#			\var_dump($r->getHeaders());
			$this::assertSame($expected[$i], $r->getHeaderLine('location'));
		}

	}

	public function testExtract():void{
		$url = $this->http->extract('https://t.co/ZSS6nVOcVp');

		$this::assertSame('https://api.guildwars2.com/v2/build', $url);
	}

}
