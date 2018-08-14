<?php
/**
 * Class Uri
 *
 * @filesource   Uri.php
 * @created      10.08.2018
 * @package      chillerlan\HTTP
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

final class Uri implements UriInterface{

	private const DEFAULT_PORTS = [
		'http'   => 80,
		'https'  => 443,
		'ftp'    => 21,
		'gopher' => 70,
		'nntp'   => 119,
		'news'   => 119,
		'telnet' => 23,
		'tn3270' => 23,
		'imap'   => 143,
		'pop'    => 110,
		'ldap'   => 389,
	];

	/**
	 * @var string
	 */
	private $scheme = '';

	/**
	 * @var string
	 */
	private $user = '';

	/**
	 * @var string
	 */
	private $pass = '';

	/**
	 * @var string
	 */
	private $host = '';

	/**
	 * @var int
	 */
	private $port = null;

	/**
	 * @var string
	 */
	private $path = '';

	/**
	 * @var string
	 */
	private $query = '';

	/**
	 * @var string
	 */
	private $fragment = '';

	/**
	 * Uri constructor.
	 *
	 * @param string|null $uri
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct(string $uri = null){

		if($uri !== ''){
			$parts = parse_url($uri);

			if($parts === false){
				throw new InvalidArgumentException('invalid URI: "'.$uri.'"');
			}

			$this->parseUriParts($parts);
		}

	}

	/**
	 * @inheritdoc
	 */
	public function __toString(){
		$this->validateState();

		$uri       = '';
		$authority = $this->getAuthority();

		if($this->scheme !== ''){
			$uri .= $this->scheme.':';
		}

		if($authority !== '' || $this->scheme === 'file'){
			$uri .= '//'.$authority;
		}

		$uri .= $this->path;

		if($this->query !== ''){
			$uri .= '?'.$this->query;
		}

		if($this->fragment !== ''){
			$uri .= '#'.$this->fragment;
		}

		return $uri;
	}

	/**
	 * Scheme
	 */

	/**
	 * @param mixed $scheme
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private function filterScheme($scheme):string{

		if(!is_string($scheme)){
			throw new InvalidArgumentException(sprintf('scheme must be a string'));
		}

		return strtolower($scheme);
	}

	/**
	 * @inheritdoc
	 */
	public function getScheme():string{
		return $this->scheme;
	}

	/**
	 * @inheritdoc
	 */
	public function withScheme($scheme):UriInterface{
		$scheme = $this->filterScheme($scheme);

		if($this->scheme === $scheme){
			return $this;
		}

		$clone         = clone $this;
		$clone->scheme = $scheme;
		$clone->removeDefaultPort();
		$clone->validateState();

		return $clone;
	}

	/**
	 * Authority
	 */

	/**
	 * @param mixed $user
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private function filterUser($user):string{

		if(!is_string($user)){
			throw new InvalidArgumentException(sprintf('user must be a string'));
		}

		return $user;
	}

	/**
	 * @param mixed $pass
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private function filterPass($pass):string{

		if(!is_string($pass)){
			throw new InvalidArgumentException(sprintf('pass must be a string'));
		}

		return $pass;
	}

	/**
	 * @inheritdoc
	 */
	public function getAuthority():string{
		$authority = $this->host;
		$userInfo  = $this->getUserInfo();

		if($userInfo !== ''){
			$authority = $userInfo.'@'.$authority;
		}

		if($this->port !== null){
			$authority .= ':'.$this->port;
		}

		return $authority;
	}

	/**
	 * @inheritdoc
	 */
	public function getUserInfo():string{
		return (string)$this->user.($this->pass != '' ? ':'.$this->pass : '');
	}

	/**
	 * @inheritdoc
	 */
	public function withUserInfo($user, $password = null):UriInterface{
		$info = $user;

		if($password !== ''){
			$info .= ':'.$password;
		}

		if($this->getUserInfo() === $info){
			return $this;
		}

		$clone       = clone $this;
		$clone->user = $user;
		$clone->pass = $password;
		$clone->validateState();

		return $clone;
	}

	/**
	 * Host
	 */

	/**
	 * @param mixed $host
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private function filterHost($host):string{

		if(!is_string($host)){
			throw new InvalidArgumentException('host must be a string');
		}

		if(filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)){
			$host = '['.$host.']';
		}

		return strtolower($host);
	}

	/**
	 * @inheritdoc
	 */
	public function getHost():string{
		return $this->host;
	}

	/**
	 * @inheritdoc
	 */
	public function withHost($host):UriInterface{
		$host = $this->filterHost($host);

		if($this->host === $host){
			return $this;
		}

		$clone       = clone $this;
		$clone->host = $host;
		$clone->validateState();

		return $clone;
	}

	/**
	 * Port
	 */

	/**
	 * @param mixed $port
	 *
	 * @return int|null
	 * @throws \InvalidArgumentException
	 */
	private function filterPort($port):?int{

		if($port === null){
			return null;
		}

		$port = (int)$port;

		if($port >= 1 && $port <= 0xffff){
			return $port;
		}

		throw new InvalidArgumentException(sprintf('invalid port: %d', $port));
	}

	/**
	 * @inheritdoc
	 */
	public function getPort():?int{
		return $this->port;
	}

	/**
	 * @inheritdoc
	 */
	public function withPort($port):UriInterface{
		$port = $this->filterPort($port);

		if($this->port === $port){
			return $this;
		}

		$clone       = clone $this;
		$clone->port = $port;
		$clone->removeDefaultPort();
		$clone->validateState();

		return $clone;
	}

	/**
	 * Path
	 */

	/**
	 * @param mixed $path
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private function filterPath($path):string{

		if(!is_string($path)){
			throw new InvalidArgumentException('path must be a string');
		}

		return $this->replaceChars($path);
	}

	/**
	 * @inheritdoc
	 */
	public function getPath():string{
		return $this->path;
	}

	/**
	 * @inheritdoc
	 */
	public function withPath($path):UriInterface{
		$path = $this->filterPath($path);

		if($this->path === $path){
			return $this;
		}

		$clone       = clone $this;
		$clone->path = $path;
		$clone->validateState();

		return $clone;
	}

	/**
	 * Query
	 */

	/**
	 * @param $query
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	private function filterQuery($query):string{

		if(!is_string($query)){
			throw new InvalidArgumentException('query and fragment must be a string');
		}

		return $this->replaceChars($query, true);
	}

	/**
	 * @inheritdoc
	 */
	public function getQuery():string{
		return $this->query;
	}

	/**
	 * @inheritdoc
	 */
	public function withQuery($query):UriInterface{
		$query = $this->filterQuery($query);

		if($this->query === $query){
			return $this;
		}

		$clone        = clone $this;
		$clone->query = $query;
		$clone->validateState();

		return $clone;
	}

	/**
	 * Fragment
	 */

	/**
	 * @param $fragment
	 *
	 * @return string
	 */
	private function filterFragment($fragment):string{
		return $this->filterQuery($fragment);
	}

	/**
	 * @inheritdoc
	 */
	public function getFragment():string{
		return $this->fragment;
	}

	/**
	 * @inheritdoc
	 */
	public function withFragment($fragment):UriInterface{
		$fragment = $this->filterFragment($fragment);

		if($this->fragment === $fragment){
			return $this;
		}

		$clone           = clone $this;
		$clone->fragment = $fragment;
		$clone->validateState();

		return $clone;
	}

	/**
	 * @param array $parts
	 *
	 * @return void
	 */
	private function parseUriParts(array $parts):void{

		foreach(['scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment'] as $part){

			if(!isset($parts[$part])){
				continue;
			}

			$this->{$part} = call_user_func_array([$this, 'filter'.ucfirst($part)], [$parts[$part]]);
		}

		$this->removeDefaultPort();
	}

	/**
	 * @param string    $str
	 * @param bool|null $query
	 *
	 * @return string
	 */
	private function replaceChars(string $str, bool $query = null):string{
		return preg_replace_callback(
			'/(?:[^'
			.'a-z\d_\-\.~'
			.'!\$&\'\(\)\*\+,;='
			.'%:@\/'.($query ? '\?' : '')
			.']++|%(?![a-f\d]{2}))/i',
			function(array $match):string{
				return rawurlencode($match[0]);
			},
			$str
		);

	}

	/**
	 * @return void
	 */
	private function removeDefaultPort():void{

		if($this->port !== null && (isset($this::DEFAULT_PORTS[$this->scheme]) && $this->port === $this::DEFAULT_PORTS[$this->scheme])){
			$this->port = null;
		}

	}

	/**
	 * @return void
	 */
	private function validateState():void{

		if(empty($this->host) && ($this->scheme === 'http' || $this->scheme === 'https')){
			$this->host = 'localhost';
		}

		if($this->getAuthority() !== ''){

			if(isset($this->path[0]) && $this->path[0] !== '/'){
				$this->path = '/'.$this->path; // automagically fix the path, unlike Guzzle
			}

		}
		else{

			if(strpos($this->path, '//') === 0){
				$this->path = '/'.ltrim($this->path, '/'); // automagically fix the path, unlike Guzzle
			}

			if(empty($this->scheme) && strpos(explode('/', $this->path, 2)[0], ':') !== false){
				throw new InvalidArgumentException('A relative URI must not have a path beginning with a segment containing a colon');
			}

		}

	}

	/**
	 * Additional methods
	 */

	/**
	 * @see \parse_url()
	 *
	 * @param array $parts
	 *
	 * @return \Psr\Http\Message\UriInterface
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
	 * @return \chillerlan\HTTP\Uri
	 */
	public function withoutQueryValue($key):Uri{
		$current = $this->getQuery();

		if($current === ''){
			return $this;
		}

		$decodedKey = rawurldecode($key);

		$result = array_filter(explode('&', $current), function($part) use ($decodedKey){
			return rawurldecode(explode('=', $part)[0]) !== $decodedKey;
		});

		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $this->withQuery(implode('&', $result));
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
	 * @return \chillerlan\HTTP\Uri
	 */
	public function withQueryValue($key, $value):Uri{
		$current = $this->getQuery();

		if($current === ''){
			$result = [];
		}
		else{
			$decodedKey = rawurldecode($key);
			$result     = array_filter(explode('&', $current), function($part) use ($decodedKey){
				return rawurldecode(explode('=', $part)[0]) !== $decodedKey;
			});
		}

		// Query string separators ("=", "&") within the key or value need to be encoded
		// (while preventing double-encoding) before setting the query string. All other
		// chars that need percent-encoding will be encoded by withQuery().
		$replaceQuery = ['=' => '%3D', '&' => '%26'];
		$key          = strtr($key, $replaceQuery);

		$result[] = $value !== null
			? $key.'='.strtr($value, $replaceQuery)
			: $key;

		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $this->withQuery(implode('&', $result));
	}

}
