<?php
/**
 * Class Uri
 *
 * @filesource   Uri.php
 * @created      10.08.2018
 * @package      chillerlan\HTTP\Psr7
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

use function call_user_func_array, explode, filter_var, is_string, ltrim, parse_url,
	preg_replace_callback, rawurlencode, strpos, strtolower, ucfirst;

use const FILTER_FLAG_IPV6, FILTER_VALIDATE_IP;

class Uri implements UriInterface{

	protected const DEFAULT_PORTS = [
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
	protected $scheme = '';

	/**
	 * @var string
	 */
	protected $user = '';

	/**
	 * @var string
	 */
	protected $pass = '';

	/**
	 * @var string
	 */
	protected $host = '';

	/**
	 * @var int
	 */
	protected $port = null;

	/**
	 * @var string
	 */
	protected $path = '';

	/**
	 * @var string
	 */
	protected $query = '';

	/**
	 * @var string
	 */
	protected $fragment = '';

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
	 * @inheritDoc
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
	protected function filterScheme($scheme):string{

		if(!is_string($scheme)){
			throw new InvalidArgumentException('scheme must be a string');
		}

		return strtolower($scheme);
	}

	/**
	 * @inheritDoc
	 */
	public function getScheme():string{
		return $this->scheme;
	}

	/**
	 * @inheritDoc
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
	protected function filterUser($user):string{

		if(!is_string($user)){
			throw new InvalidArgumentException('user must be a string');
		}

		return $user;
	}

	/**
	 * @param mixed $pass
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	protected function filterPass($pass):string{

		if(!is_string($pass)){
			throw new InvalidArgumentException('pass must be a string');
		}

		return $pass;
	}

	/**
	 * @inheritDoc
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
	 * @inheritDoc
	 */
	public function getUserInfo():string{
		return (string)$this->user.($this->pass != '' ? ':'.$this->pass : '');
	}

	/**
	 * @inheritDoc
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
	protected function filterHost($host):string{

		if(!is_string($host)){
			throw new InvalidArgumentException('host must be a string');
		}

		if(filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)){
			$host = '['.$host.']';
		}

		return strtolower($host);
	}

	/**
	 * @inheritDoc
	 */
	public function getHost():string{
		return $this->host;
	}

	/**
	 * @inheritDoc
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
	protected function filterPort($port):?int{

		if($port === null){
			return null;
		}

		$port = (int)$port;

		if($port >= 1 && $port <= 0xffff){
			return $port;
		}

		throw new InvalidArgumentException('invalid port: '.$port);
	}

	/**
	 * @inheritDoc
	 */
	public function getPort():?int{
		return $this->port;
	}

	/**
	 * @inheritDoc
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
	protected function filterPath($path):string{

		if(!is_string($path)){
			throw new InvalidArgumentException('path must be a string');
		}

		return $this->replaceChars($path);
	}

	/**
	 * @inheritDoc
	 */
	public function getPath():string{
		return $this->path;
	}

	/**
	 * @inheritDoc
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
	protected function filterQuery($query):string{

		if(!is_string($query)){
			throw new InvalidArgumentException('query and fragment must be a string');
		}

		return $this->replaceChars($query, true);
	}

	/**
	 * @inheritDoc
	 */
	public function getQuery():string{
		return $this->query;
	}

	/**
	 * @inheritDoc
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
	protected function filterFragment($fragment):string{
		return $this->filterQuery($fragment);
	}

	/**
	 * @inheritDoc
	 */
	public function getFragment():string{
		return $this->fragment;
	}

	/**
	 * @inheritDoc
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
	protected function parseUriParts(array $parts):void{

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
	protected function replaceChars(string $str, bool $query = null):string{
		/** @noinspection RegExpRedundantEscape */
		return preg_replace_callback(
			'/(?:[^a-z\d_\-\.~!\$&\'\(\)\*\+,;=%:@\/'.($query ? '\?' : '').']++|%(?![a-f\d]{2}))/i',
			function(array $match):string{
				return rawurlencode($match[0]);
			},
			$str
		);

	}

	/**
	 * @return void
	 */
	protected function removeDefaultPort():void{

		if($this->port !== null && (isset($this::DEFAULT_PORTS[$this->scheme]) && $this->port === $this::DEFAULT_PORTS[$this->scheme])){
			$this->port = null;
		}

	}

	/**
	 * @return void
	 */
	protected function validateState():void{

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

}
