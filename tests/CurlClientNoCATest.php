<?php
/**
 * Class CurlClientNoCATest
 *
 * @created      28.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\CurlClient;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Client\ClientInterface;

/**
 *
 */
#[Group('slow')]
class CurlClientNoCATest extends HTTPClientTestAbstract{

	protected function initClient():ClientInterface{
		$this->options->ca_info        = null;
		$this->options->ssl_verifypeer = false;

		return new CurlClient($this->responseFactory, $this->options);
	}

}
