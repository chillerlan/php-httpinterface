<?php
/**
 * Class GuzzleClientTest
 *
 * @filesource   GuzzleClientTest.php
 * @created      23.10.2017
 * @package      chillerlan\HTTPTest
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\GuzzleClient;
use GuzzleHttp\Client;

class GuzzleClientTest extends HTTPClientTestAbstract{

	protected $FQCN = GuzzleClient::class;

	protected function setUp(){
		parent::setUp();

		$client = new Client([
			'cacert' => self::CACERT,
			'headers' => ['User-Agent' => self::USER_AGENT]
		]);

		$this->http = new GuzzleClient($this->options, $client);
	}

}
