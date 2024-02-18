<?php
/**
 * Class UriFactory
 *
 * @created      27.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTP\Psr17;

use chillerlan\HTTP\Psr7\Uri;
use Psr\Http\Message\{UriFactoryInterface, UriInterface};

/**
 *
 */
class UriFactory implements UriFactoryInterface{

	/**
	 * @inheritDoc
	 */
	public function createUri(string $uri = ''):UriInterface{
		return new Uri($uri);
	}

}
