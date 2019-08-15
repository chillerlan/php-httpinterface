<?php
/**
 * Class URLExtractorTest
 *
 * @filesource   URLExtractorTest.php
 * @created      15.08.2019
 * @package      chillerlan\HTTPTest\Psr18
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr18;

use chillerlan\HTTP\HTTPOptions;
use chillerlan\HTTP\Psr18\URLExtractor;
use chillerlan\HTTP\Psr7\Request;

/**
 * @property \chillerlan\HTTP\Psr18\URLExtractor $http
 */
class URLExtractorTest extends HTTPClientTestAbstract{

	protected function setUp():void{
		$options = new HTTPOptions([
			'ca_info'      => __DIR__.'/../cacert.pem',
			'user_agent'   => $this::USER_AGENT,
		]);

		$this->http = new URLExtractor($options);
	}


	public function testSendRequest(){
		// reminder: twitter does not delete shortened URLs of deleted tweets (this one was deleted in 2016)
		$this->http->sendRequest(new Request('GET', 'https://t.co/ZSS6nVOcVp'));

		$expected = [
			'http://bit.ly/1oesmr8',
			'http://tinyurl.com/jvc5y98',
			'https://api.guildwars2.com/v2/build',
			'',
		];

		foreach($this->http->getResponses() as $i => $r){
			$this->assertSame($expected[$i], $r->getHeaderLine('location'));
		}

	}

}
