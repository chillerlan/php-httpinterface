<?php
/**
 * Class CurlClientNoCAFactory
 *
 * @created      14.03.2024
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2024 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTPTest\ClientFactories;

use chillerlan\HTTP\{CurlClient, HTTPOptions};
use chillerlan\HTTPTest\HTTPClientTestAbstract;
use chillerlan\PHPUnitHttp\HttpClientFactoryInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final class CurlClientNoCAFactory implements HttpClientFactoryInterface{

	public function getClient(string $cacert, ResponseFactoryInterface $responseFactory):ClientInterface{
		$options                 = new HTTPOptions;
		$options->ca_info        = null;
		$options->ssl_verifypeer = false;
		$options->user_agent     = HTTPClientTestAbstract::USER_AGENT;

		return new CurlClient($responseFactory, $options);
	}

}
