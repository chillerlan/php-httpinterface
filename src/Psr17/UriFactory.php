<?php
/**
 * Class UriFactory
 *
 * @filesource   UriFactory.php
 * @created      27.08.2018
 * @package      chillerlan\HTTP\Psr17
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr17;

use chillerlan\HTTP\Psr7\Uri;
use Psr\Http\Message\{UriFactoryInterface, UriInterface};

final class UriFactory implements UriFactoryInterface{

	/**
	 * Create a new URI.
	 *
	 * @param string $uri
	 *
	 * @return \Psr\Http\Message\UriInterface|\chillerlan\HTTP\Psr7\Uri
	 *
	 * @throws \InvalidArgumentException If the given URI cannot be parsed.
	 */
	public function createUri(string $uri = ''):UriInterface{
		return new Uri($uri);
	}

}
