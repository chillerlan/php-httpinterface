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

use chillerlan\HTTP\Psr18\{ClientException, CurlHandle};

trait HTTPOptionsTrait{

	/**
	 * @var string
	 */
	protected $user_agent = 'chillerlanHttpInterface/2.0 +https://github.com/chillerlan/php-httpinterface';

	/**
	 * options for each curl instance
	 *
	 * this array is being merged into the default options as the last thing before curl_exec().
	 * none of the values (except existence of the CA file) will be checked - that's up to the implementation.
	 *
	 * @var array
	 */
	protected $curl_options = [];

	/**
	 * CA Root Certificates for use with CURL/SSL (if not configured in php.ini or available in a default path)
	 *
	 * @var string
	 *
	 * @link https://curl.haxx.se/docs/caextract.html
	 * @link https://curl.haxx.se/ca/cacert.pem
	 * @link https://raw.githubusercontent.com/bagder/ca-bundle/master/ca-bundle.crt
	 */
	protected $ca_info = null;

	/**
	 * see CURLOPT_SSL_VERIFYPEER
	 * requires either HTTPOptions::$ca_info or a properly working system CA file
	 *
	 * @var bool
	 * @link http://php.net/manual/en/function.curl-setopt.php
	 */
	protected $ssl_verifypeer = true;

	/**
	 * The CurlHandleInterface to use in CurlClient::sendRequest()
	 *
	 * @var string
	 */
	protected $curlHandle = CurlHandle::class;

	/**
	 * HTTPOptionsTrait constructor
	 *
	 * @throws \Psr\Http\Client\ClientExceptionInterface
	 */
	protected function HTTPOptionsTrait():void{

		if(!\is_array($this->curl_options)){
			$this->curl_options = [];
		}

		if(!\is_string($this->user_agent) || empty(\trim($this->user_agent))){
			throw new ClientException('invalid user agent');
		}

		$this->setCA();
	}

	/**
	 * @return void
	 * @throws \Psr\Http\Client\ClientExceptionInterface
	 */
	protected function setCA():void{

		// disable verification if wanted so
		if($this->ssl_verifypeer !== true || (isset($this->curl_options[\CURLOPT_SSL_VERIFYPEER]) && !$this->curl_options[\CURLOPT_SSL_VERIFYPEER])){
			unset($this->curl_options[\CURLOPT_CAINFO], $this->curl_options[\CURLOPT_CAPATH]);

			$this->curl_options[\CURLOPT_SSL_VERIFYHOST] = 0;
			$this->curl_options[\CURLOPT_SSL_VERIFYPEER] = false;

			return;
		}

		$this->curl_options[\CURLOPT_SSL_VERIFYHOST] = 2;
		$this->curl_options[\CURLOPT_SSL_VERIFYPEER] = true;

		// a path/dir/link to a CA bundle is given, let's check that
		if(\is_string($this->ca_info)){

			// if you - for whatever obscure reason - need to check Windows .lnk links,
			// see http://php.net/manual/en/function.is-link.php#91249
			switch(true){
				case \is_dir($this->ca_info):
				case \is_link($this->ca_info) && \is_dir(\readlink($this->ca_info)): // @codeCoverageIgnore
					$this->curl_options[\CURLOPT_CAPATH] = $this->ca_info;
					unset($this->curl_options[\CURLOPT_CAINFO]);
					return;

				case \is_file($this->ca_info):
				case \is_link($this->ca_info) && \is_file(\readlink($this->ca_info)): // @codeCoverageIgnore
					$this->curl_options[\CURLOPT_CAINFO] = $this->ca_info;
					unset($this->curl_options[\CURLOPT_CAPATH]);
					return;
			}

			throw new ClientException('invalid path to SSL CA bundle (HTTPOptions::$ca_info): '.$this->ca_info);
		}

		// we somehow landed here, so let's check if there's a CA bundle given via the cURL options
		$ca = $this->curl_options[\CURLOPT_CAPATH] ?? $this->curl_options[\CURLOPT_CAINFO] ?? false;

		if($ca){

			// just check if the file/path exists
			switch(true){
				case \is_dir($ca):
				case \is_link($ca) && \is_dir(\readlink($ca)): // @codeCoverageIgnore
					unset($this->curl_options[\CURLOPT_CAINFO]);
					return;

				case \is_file($ca):
				case \is_link($ca) && \is_file(\readlink($ca)): // @codeCoverageIgnore
					return;
			}

			throw new ClientException('invalid path to SSL CA bundle (CURLOPT_CAPATH/CURLOPT_CAINFO): '.$ca);
		}

		// check php.ini options - PHP should find the file by itself
		if(\file_exists(\ini_get('curl.cainfo'))){
			return; // @codeCoverageIgnore
		}

		// this is getting weird. as a last resort, we're going to check some default paths for a CA bundle file
		$cafiles = [
			// check other php.ini settings
			\ini_get('openssl.cafile'),
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
			if(\is_file($file) || (\is_link($file) && \is_file(\readlink($file)))){
				$this->curl_options[\CURLOPT_CAINFO] = $file;
				$this->ca_info = $file;
				return;
			}
		}

		// @codeCoverageIgnoreStart
		$msg = 'No system CA bundle could be found in any of the the common system locations. '
			.'In order to verify peer certificates, you will need to supply the path on disk to a certificate bundle via  '
			.'HTTPOptions::$ca_info or HTTPOptions::$curl_options. If you do not need a specific certificate bundle, '
			.'then you can download a CA bundle over here: https://curl.haxx.se/docs/caextract.html. '
			.'Once you have a CA bundle available on disk, you can set the "curl.cainfo" php.ini setting to point '
			.'to the path of the file, allowing you to omit the $ca_info or $curl_options setting. '
			.'See http://curl.haxx.se/docs/sslcerts.html for more information.';

		throw new ClientException($msg);
		// @codeCoverageIgnoreEnd
	}

}
