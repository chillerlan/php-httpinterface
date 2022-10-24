<?php
/**
 * cURL multi example, fetch data from the GUildWars2 items API
 * @link         https://wiki.guildwars2.com/wiki/API:2/items
 *
 * @filesource   curl_multi.php
 * @created      08.11.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

use chillerlan\HTTP\Utils\{MessageUtil, QueryUtil};
use chillerlan\HTTP\CurlUtils\{CurlMultiClient, MultiResponseHandlerInterface};
use chillerlan\HTTP\HTTPOptions;
use chillerlan\HTTP\Psr18\CurlClient;
use chillerlan\HTTP\Psr7\Request;
use Psr\Http\Message\{RequestInterface, ResponseInterface};

require_once __DIR__.'/../vendor/autoload.php';

// options for both clients
$options = new HTTPOptions([
	'ca_info'    => __DIR__.'/cacert.pem',
	'sleep'      => 60 / 300 * 1000000, // GW2 API limit: 300 requests/minute
#	'user_agent' => 'my fancy http client',
]);

$client = new CurlClient($options);

$endpoint     = 'https://api.guildwars2.com/v2/items';
$languages    = ['de', 'en', 'es'];//, 'fr', 'zh'
// request the list of item ids
$itemResponse = $client->sendRequest(new Request('GET', $endpoint));

if($itemResponse->getStatusCode() !== 200){
	exit('/v2/items fetch error');
}

// create directories for each language to dump the item responses into
foreach($languages as $lang){
	$dir = __DIR__.'/'.$lang;

	if(!file_exists($dir)){
		mkdir($dir);
	}
}

// the multi request handler
$handler = new class() implements MultiResponseHandlerInterface{

	public function handleResponse(ResponseInterface $response, RequestInterface $request, int $id, array $curl_info):?RequestInterface{

		// the API returns either 200 or 206 on OK responses
		// https://gitter.im/arenanet/api-cdi?at=5738e2c0ae26c1967f9eb4a0
		if(in_array($response->getStatusCode(), [200, 206], true)){
			$lang = $response->getHeaderLine('content-language');

			// create a file for each item in the response (ofc you'd rather put this in a DB)
			foreach(MessageUtil::decodeJSON($response) as $item){
				$file = $lang.'/'.$item->id;
				file_put_contents(__DIR__.'/'.$file.'.json', json_encode($item, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

				echo $file.PHP_EOL;
			}

			// response ok, nothing to return
			return null;
		}

		// return the failed request back to the stack
		return $request;
	}

};

$multiClient = new CurlMultiClient($handler, $options);

// chunk the item response into arrays of 200 ids each (API limit) and create Request objects for each desired language
foreach(array_chunk(MessageUtil::decodeJSON($itemResponse), 200) as $chunk){
	foreach($languages as $lang){
		$multiClient->addRequest(new Request('GET', $endpoint.'?'.QueryUtil::build(['lang' => $lang, 'ids' => implode(',', $chunk)])));
	}
}

// run the whole thing
$multiClient->process();
