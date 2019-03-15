<?php
/**
 * Class UriExtended
 *
 * @see https://github.com/guzzle/psr7/blob/31ea59d632d3ac145300fffb2873a195172c0814/src/Uri.php
 *
 * @filesource   UriExtended.php
 * @created      06.03.2019
 * @package      chillerlan\HTTP\Psr7
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use Psr\Http\Message\UriInterface;

/**
 * Additional non-PSR Uri methods
 */
class UriExtended extends Uri{

	/**
	 * @see \parse_url()
	 *
	 * @param array $parts
	 *
	 * @return \Psr\Http\Message\UriInterface|\chillerlan\HTTP\Psr7\Uri
	 */
	public static function fromParts(array $parts):UriInterface{
		$uri = new self;

		$uri->parseUriParts($parts);
		$uri->validateState();

		return $uri;
	}

	/**
	 * Whether the URI is absolute, i.e. it has a scheme.
	 *
	 * An instance of UriInterface can either be an absolute URI or a relative reference. This method returns true
	 * if it is the former. An absolute URI has a scheme. A relative reference is used to express a URI relative
	 * to another URI, the base URI. Relative references can be divided into several forms:
	 * - network-path references, e.g. '//example.com/path'
	 * - absolute-path references, e.g. '/path'
	 * - relative-path references, e.g. 'subpath'
	 *
	 * @return bool
	 * @see  Uri::isNetworkPathReference
	 * @see  Uri::isAbsolutePathReference
	 * @see  Uri::isRelativePathReference
	 * @link https://tools.ietf.org/html/rfc3986#section-4
	 */
	public function isAbsolute():bool{
		return $this->getScheme() !== '';
	}

	/**
	 * Whether the URI is a network-path reference.
	 *
	 * A relative reference that begins with two slash characters is termed an network-path reference.
	 *
	 * @return bool
	 * @link https://tools.ietf.org/html/rfc3986#section-4.2
	 */
	public function isNetworkPathReference():bool{
		return $this->getScheme() === '' && $this->getAuthority() !== '';
	}

	/**
	 * Whether the URI is a absolute-path reference.
	 *
	 * A relative reference that begins with a single slash character is termed an absolute-path reference.
	 *
	 * @return bool
	 * @link https://tools.ietf.org/html/rfc3986#section-4.2
	 */
	public function isAbsolutePathReference():bool{
		return $this->getScheme() === '' && $this->getAuthority() === '' && isset($this->getPath()[0]) && $this->getPath()[0] === '/';
	}

	/**
	 * Whether the URI is a relative-path reference.
	 *
	 * A relative reference that does not begin with a slash character is termed a relative-path reference.
	 *
	 * @return bool
	 * @link https://tools.ietf.org/html/rfc3986#section-4.2
	 */
	public function isRelativePathReference():bool{
		return $this->getScheme() === '' && $this->getAuthority() === '' && (!isset($this->getPath()[0]) || $this->getPath()[0] !== '/');
	}

	/**
	 * removes a specific query string value.
	 *
	 * Any existing query string values that exactly match the provided key are
	 * removed.
	 *
	 * @param string $key Query string key to remove.
	 *
	 * @return \Psr\Http\Message\UriInterface|\chillerlan\HTTP\Psr7\UriExtended
	 */
	public function withoutQueryValue($key):UriExtended{
		$current = $this->getQuery();

		if($current === ''){
			return $this;
		}

		$decodedKey = \rawurldecode($key);

		$result = \array_filter(\explode('&', $current), function($part) use ($decodedKey){
			return \rawurldecode(\explode('=', $part)[0]) !== $decodedKey;
		});

		return $this->withQuery(\implode('&', $result));
	}

	/**
	 * adds a specific query string value.
	 *
	 * Any existing query string values that exactly match the provided key are
	 * removed and replaced with the given key value pair.
	 *
	 * A value of null will set the query string key without a value, e.g. "key"
	 * instead of "key=value".
	 *
	 * @param string      $key   Key to set.
	 * @param string|null $value Value to set
	 *
	 * @return \Psr\Http\Message\UriInterface|\chillerlan\HTTP\Psr7\UriExtended
	 */
	public function withQueryValue($key, $value):UriExtended{
		$current = $this->getQuery();

		if($current === ''){
			$result = [];
		}
		else{
			$decodedKey = \rawurldecode($key);
			$result     = \array_filter(\explode('&', $current), function($part) use ($decodedKey){
				return \rawurldecode(\explode('=', $part)[0]) !== $decodedKey;
			});
		}

		// Query string separators ("=", "&") within the key or value need to be encoded
		// (while preventing double-encoding) before setting the query string. All other
		// chars that need percent-encoding will be encoded by withQuery().
		$replaceQuery = ['=' => '%3D', '&' => '%26'];
		$key          = \strtr($key, $replaceQuery);

		$result[] = $value !== null
			? $key.'='.\strtr($value, $replaceQuery)
			: $key;

		return $this->withQuery(\implode('&', $result));
	}

}
