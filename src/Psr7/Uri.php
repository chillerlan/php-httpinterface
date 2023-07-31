<?php
/**
 * Class Uri
 *
 * @created      10.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use chillerlan\HTTP\Utils\UriUtil;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

use function call_user_func_array, explode, filter_var, is_array, is_string, ltrim, mb_strtolower,
	preg_replace_callback, rawurlencode, strtolower, str_contains, str_starts_with, ucfirst, var_export;

use const FILTER_FLAG_IPV6, FILTER_VALIDATE_IP;

class Uri implements UriInterface{

	protected string  $scheme   = '';
	protected string  $user     = '';
	protected ?string $pass     = null;
	protected string  $host     = '';
	protected ?int    $port     = null;
	protected string  $path     = '';
	protected string  $query    = '';
	protected string  $fragment = '';

	/**
	 * Uri constructor.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct(string|array $uri = null){

		if($uri !== null){

			if(is_string($uri)){
				$uri = UriUtil::parseUrl($uri);
			}

			if(!is_array($uri)){
				throw new InvalidArgumentException('invalid URI: '.var_export($uri, true));
			}

			$this->parseUriParts($uri);
			$this->validateState();
		}

	}

	/**
	 * @inheritDoc
	 */
	public function __toString():string{
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

	/*
	 * Scheme
	 */

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function filterScheme(mixed $scheme):string{

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
	public function withScheme($scheme):static{
		$scheme = $this->filterScheme($scheme);

		if($scheme !== $this->scheme){
			$this->scheme = $scheme;

			$this->removeDefaultPort();
			$this->validateState();
		}

		return $this;
	}

	/*
	 * Authority
	 */

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function filterUser(mixed $user):string{

		if(!is_string($user)){
			throw new InvalidArgumentException('user must be a string');
		}

		return $user;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function filterPass(mixed $pass):string{

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
		return $this->user.($this->pass != '' ? ':'.$this->pass : '');
	}

	/**
	 * @inheritDoc
	 */
	public function withUserInfo($user, $password = null):static{
		$info = $user;

		if($password !== null && $password !== ''){
			$info .= ':'.$password;
		}

		if($info !== $this->getUserInfo()){
			$this->user = $user;
			$this->pass = $password;

			$this->validateState();
		}

		return $this;
	}

	/*
	 * Host
	 */

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function filterHost(mixed $host):string{

		if(!is_string($host)){
			throw new InvalidArgumentException('host must be a string');
		}

		if(filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)){
			$host = '['.$host.']';
		}

		return mb_strtolower($host);
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
	public function withHost($host):static{
		$host = $this->filterHost($host);

		if($host !== $this->host){
			$this->host = $host;

			$this->validateState();
		}

		return $this;
	}

	/*
	 * Port
	 */

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function filterPort(mixed $port):?int{

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
	public function withPort($port):static{
		$port = $this->filterPort($port);

		if($port !== $this->port){
			$this->port = $port;

			$this->removeDefaultPort();
			$this->validateState();
		}

		return $this;
	}

	/*
	 * Path
	 */

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function filterPath(mixed $path):string{

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
	public function withPath($path):static{
		$path = $this->filterPath($path);

		if($path !== $this->path){
			$this->path = $path;

			$this->validateState();
		}

		return $this;
	}

	/*
	 * Query
	 */

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function filterQuery(mixed $query):string{

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
	public function withQuery($query):static{
		$query = $this->filterQuery($query);

		if($query !== $this->query){
			$this->query = $query;

			$this->validateState();
		}

		return $this;
	}

	/*
	 * Fragment
	 */

	/**
	 *
	 */
	protected function filterFragment(mixed $fragment):string{
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
	public function withFragment($fragment):static{
		$fragment = $this->filterFragment($fragment);

		if($fragment !== $this->fragment){
			$this->fragment = $fragment;

			$this->validateState();
		}

		return $this;
	}

	/**
	 *
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
	 *
	 */
	protected function replaceChars(string $str, bool $query = null):string{
		/** @noinspection RegExpRedundantEscape, RegExpUnnecessaryNonCapturingGroup */
		return preg_replace_callback(
			'/(?:[^a-z\d_\-\.~!\$&\'\(\)\*\+,;=%:@\/'.($query ? '\?' : '').']++|%(?![a-f\d]{2}))/i',
			fn(array $match):string => rawurlencode($match[0]),
			$str
		);

	}

	/**
	 *
	 */
	protected function removeDefaultPort():void{

		if(UriUtil::isDefaultPort($this)){
			$this->port = null;
		}

	}

	/**
	 *
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

			if(str_starts_with($this->path, '//')){
				$this->path = '/'.ltrim($this->path, '/'); // automagically fix the path, unlike Guzzle
			}

			if(empty($this->scheme) && str_contains(explode('/', $this->path, 2)[0], ':')){
				throw new InvalidArgumentException('A relative URI must not have a path beginning with a segment containing a colon');
			}

		}

	}

}
