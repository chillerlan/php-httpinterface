<?php
/**
 * Trait HTTPOptionsTrait
 *
 * @created      28.08.2018
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2018 Smiley
 * @license      MIT
 *
 * @phan-file-suppress PhanTypeInvalidThrowsIsInterface
 */

namespace chillerlan\HTTP;

use chillerlan\HTTP\Psr18\ClientException;
use function file_exists, ini_get, is_dir, is_file, is_link, readlink, trim;
use const CURLOPT_CAINFO, CURLOPT_CAPATH;

trait HTTPOptionsTrait{

	/**
	 * A custom user agent string
	 */
	protected string $user_agent = 'chillerlanHttpInterface/6.0 +https://github.com/chillerlan/php-httpinterface';

	/**
	 * options for each curl instance
	 *
	 * this array is being merged into the default options as the last thing before curl_exec().
	 * none of the values (except existence of the CA file) will be checked - that's up to the implementation.
	 */
	protected array $curl_options = [];

	/**
	 * CA Root Certificates for use with CURL/SSL (if not configured in php.ini or available in a default path)
	 *
	 * @link https://curl.se/docs/caextract.html
	 * @link https://curl.se/ca/cacert.pem
	 * @link https://raw.githubusercontent.com/bagder/ca-bundle/master/ca-bundle.crt
	 */
	protected string|null $ca_info = null;

	/**
	 * @internal
	 */
	protected bool $ca_info_is_path = false;

	/**
	 * see CURLOPT_SSL_VERIFYPEER
	 * requires either HTTPOptions::$ca_info or a properly working system CA file
	 *
	 * @link https://php.net/manual/function.curl-setopt.php
	 */
	protected bool $ssl_verifypeer = true;

	/**
	 * options for the curl multi instance
	 *
	 * @link https://www.php.net/manual/function.curl-multi-setopt.php
	 */
	protected array $curl_multi_options = [];

	/**
	 * maximum of concurrent requests for curl_multi
	 */
	protected int $window_size = 5;

	/**
	 * sleep timer (milliseconds) between each fired multi request on startup
	 */
	protected int|null $sleep = null;

	/**
	 * Timeout value
	 *
	 * @see \CURLOPT_TIMEOUT
	 */
	protected int $timeout = 10;

	/**
	 * Number of retries (multi fetch)
	 */
	protected int $retries = 3;

	/**
	 * cURL extra hardening
	 *
	 * When set to true, cURL validates that the server staples an OCSP response during the TLS handshake.
	 *
	 * Use with caution as cURL will refuse a connection if it doesn't receive a valid OCSP response -
	 * this does not necessarily mean that the TLS connection is insecure.
	 *
	 * @see \CURLOPT_SSL_VERIFYSTATUS
	 */
	protected bool $curl_check_OCSP = false;

	/**
	 * HTTPOptionsTrait constructor
	 *
	 * @throws \Psr\Http\Client\ClientExceptionInterface
	 */
	protected function HTTPOptionsTrait():void{

		if(empty(trim($this->user_agent))){
			throw new ClientException('invalid user agent');
		}

	}

	/**
	 *
	 */
	protected function set_ca_info(string|null $ca_info = null):void{
		$this->setCA($ca_info);
	}

	/**
	 *
	 */
	protected function set_curl_options(array $curl_options):void{
		$ca_info = null;

		// let's check if there's a CA bundle given via the cURL options and move it to the ca_info option instead
		foreach([CURLOPT_CAPATH, CURLOPT_CAINFO] as $opt){

			if(isset($curl_options[$opt])){
				$ca_info = $curl_options[$opt];

				unset($curl_options[$opt]);
			}
		}

		$this->curl_options = $curl_options;

		if($ca_info){
			$this->setCA($ca_info);
		}

	}

	/**
	 * @throws \Psr\Http\Client\ClientExceptionInterface
	 */
	protected function setCA(string|null $ca_info = null):void{
		$this->ca_info = null;

		// a path/dir/link to a CA bundle is given, let's check that
		if($ca_info !== null){

			if($this->checkCA($ca_info)){
				$this->ca_info = $ca_info;

				return;
			}

			throw new ClientException('invalid path to SSL CA bundle: '.$ca_info);
		}

		// check php.ini options - PHP should find the file by itself
		if(file_exists(ini_get('curl.cainfo'))){
			return; // @codeCoverageIgnore
		}

		// this is getting weird. as a last resort, we're going to check some default paths for a CA bundle file
		if($this->checkCaDefaultLocations()){
			return;
		}

		// @codeCoverageIgnoreStart
		$msg = 'No system CA bundle could be found in any of the the common system locations. '
		       .'In order to verify peer certificates, you will need to supply the path on disk to a certificate bundle via  '
		       .'HTTPOptions::$ca_info. If you do not need a specific certificate bundle, '
		       .'then you can download a CA bundle over here: https://curl.haxx.se/docs/caextract.html. '
		       .'Once you have a CA bundle available on disk, you can set the "curl.cainfo" php.ini setting to point '
		       .'to the path of the file, allowing you to omit the $ca_info setting. '
		       .'See https://curl.se/docs/sslcerts.html for more information.';

		throw new ClientException($msg);
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Check default locations for the CA bundle
	 *
	 * @internal
	 */
	protected function checkCaDefaultLocations():bool{

		$cafiles = [
			// check other php.ini settings
			ini_get('openssl.cafile'),
			// Red Hat, CentOS, Fedora (provided by the ca-certificates package)
			'/etc/pki/tls/certs/ca-bundle.crt',
			// Ubuntu, Debian (provided by the ca-certificates package)
			'/etc/ssl/certs/ca-certificates.crt',
			// FreeBSD (provided by the ca_root_nss package)
			'/usr/local/share/certs/ca-root-nss.crt',
			// SLES 12 (provided by the ca-certificates package)
			'/var/lib/ca-certificates/ca-bundle.pem',
			// OS X provided by homebrew (using the default path)
			'/usr/local/etc/openssl/cert.pem',
			// Google app engine
			'/etc/ca-certificates.crt',
			// Windows?
			// http://php.net/manual/en/function.curl-setopt.php#110457
			'C:\\Windows\\system32\\curl-ca-bundle.crt',
			'C:\\Windows\\curl-ca-bundle.crt',
			'C:\\Windows\\system32\\cacert.pem',
			'C:\\Windows\\cacert.pem',
			// working path
			__DIR__.'/cacert.pem',
		];

		foreach($cafiles as $file){

			if(is_file($file) || (is_link($file) && is_file(readlink($file)))){
				$this->ca_info         = $file;
				$this->ca_info_is_path = false;

				return true;
			}

		}

		return false; // @codeCoverageIgnore
	}

	/**
	 * Check whether the given CA info exists and if it is file or dir
	 *
	 * @phan-suppress PhanTypeMismatchArgumentNullableInternal
	 */
	protected function checkCA(string|null $ca = null):bool{
		// if you - for whatever obscure reason - need to check Windows .lnk links,
		// see http://php.net/manual/en/function.is-link.php#91249
		switch(true){
			case is_dir($ca):
			case is_link($ca) && is_dir(readlink($ca)): // @codeCoverageIgnore
				$this->ca_info_is_path = true;

				return true;

			case is_file($ca):
			case is_link($ca) && is_file(readlink($ca)): // @codeCoverageIgnore
				$this->ca_info_is_path = false;

				return true;
		}

		return false;
	}

}
