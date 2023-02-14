<?php
/**
 * Fetch data from the Spotify web API
 *
 * @created      06.09.2021
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2021 smiley
 * @license      MIT
 */

use chillerlan\HTTP\HTTPOptions;
use chillerlan\HTTP\Psr18\CurlClient;
use chillerlan\HTTP\Utils\MessageUtil;
use chillerlan\HTTP\Psr7\{Request, Uri};

require_once __DIR__.'/../vendor/autoload.php';

$artists = ['4wLIbcoqmqI4WZHDiBxeCB', '7mefbdlQXxJVKgEbfAeKjL', '4G3PykZuN4ts87LgYKI9Zu'];

// https://github.com/ThirumalaiK/youtube-dl/commit/120339ec1413bca0a398cdcb7b4d12c5897ce7b7
$sha256Hash = 'd66221ea13998b2f81883c5187d174c8646e4041d67f5b1e103bc262d447e3a0'; // Apollo/GraphQL thing?? may change

$http = new CurlClient(new HTTPOptions([
	'ca_info'    => __DIR__.'/cacert.pem',
	'user_agent' => 'Mozilla/5.0 (Windows NT 6.6.6; Win64; x64; rv:91.0) Gecko/20100101 Firefox/91.0',
]));

$tokenRequest  = new Request('GET', new Uri('https://open.spotify.com/get_access_token'));
$tokenResponse = $http->sendRequest($tokenRequest);

if($tokenResponse->getStatusCode() !== 200){
	throw new RuntimeException('could not obtain token');
}

$token    = MessageUtil::decodeJSON($tokenResponse);
$queryUri = new Uri('https://api-partner.spotify.com/pathfinder/v1/query');
$params   = [
	'operationName' => 'queryArtistOverview',
	'extensions'    => json_encode(['persistedQuery' => ['version' => 1, 'sha256Hash' => $sha256Hash]]),
];

foreach($artists as $artistID){
	$params['variables'] = json_encode(['uri' => sprintf('spotify:artist:%s', $artistID)]);

	$request  = new Request('GET', $queryUri->withQuery(http_build_query($params)));
	$request  = $request->withHeader('Authorization', sprintf('Bearer %s', $token->accessToken));
	/** @phan-suppress-next-line PhanTypeMismatchArgumentSuperType */
	$response = $http->sendRequest($request);

	if($response->getStatusCode() === 200){
		file_put_contents(sprintf('%s/json/%s.json', __DIR__, $artistID), (string)$response->getBody());
	}

}

