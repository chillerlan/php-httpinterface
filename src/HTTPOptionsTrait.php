<?php
/**
 * Trait HTTPOptionsTrait
 *
 * @filesource   HTTPOptionsTrait.php
 * @created      28.08.2018
 * @package      chillerlan\HTTP
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2018 Smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

trait HTTPOptionsTrait{

	/**
	 * @var string
	 */
	public $user_agent = 'chillerlanHttpInterface/2.0 +https://github.com/chillerlan/php-httpinterface';

	/**
	 * options for each curl instance
	 *
	 * this array is being merged into the default options as the last thing before curl_exec().
	 * none of the values (except existence of the CA file) will be checked - that's up to the implementation.
	 *
	 * @var array
	 */
	public $curl_options = [];

	/**
	 * CA Root Certificates for use with CURL/SSL (if not configured in php.ini)
	 *
	 * @var string
	 * @link https://curl.haxx.se/ca/cacert.pem
	 * @link https://raw.githubusercontent.com/bagder/ca-bundle/master/ca-bundle.crt
	 */
	public $ca_info = null;

	/**
	 * HTTPOptionsTrait constructor
	 *
	 * @throws \chillerlan\HTTP\ClientException
	 */
	protected function HTTPOptionsTrait():void{

		if(!is_array($this->curl_options)){
			$this->curl_options = [];
		}

		// we cannot verify a peer against a non-existent ca file, so turn it off in that case
		if(!$this->ca_info || !is_file($this->ca_info)
		   || (isset($this->curl_options[CURLOPT_CAINFO]) && !is_file($this->curl_options[CURLOPT_CAINFO]))){

			$this->curl_options += [
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_CAINFO         => null,
			];
		}

		if(!is_string($this->user_agent) || empty(trim($this->user_agent))){
			throw new ClientException('invalid user agent');
		}
	}

}
