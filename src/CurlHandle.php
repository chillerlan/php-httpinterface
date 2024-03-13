<?php
/**
 * Class CurlHandle
 *
 * @created      30.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTP;

use chillerlan\HTTP\Utils\HeaderUtil;
use chillerlan\Settings\SettingsContainerInterface;
use Psr\Http\Message\{RequestInterface, ResponseInterface, StreamInterface};
use CurlHandle as CH;
use function count, curl_close, curl_errno, curl_error, curl_exec, curl_getinfo, curl_init, curl_setopt_array, explode,
	file_exists, in_array, ini_get, is_dir, is_file, is_link, readlink, realpath, sprintf, strlen, strtoupper, substr, trim;
use const CURL_HTTP_VERSION_2TLS, CURLE_COULDNT_CONNECT, CURLE_COULDNT_RESOLVE_HOST, CURLE_COULDNT_RESOLVE_PROXY,
	CURLE_GOT_NOTHING, CURLE_OPERATION_TIMEOUTED, CURLE_SSL_CONNECT_ERROR, CURLOPT_CAINFO, CURLOPT_CAPATH,
	CURLOPT_CONNECTTIMEOUT, CURLOPT_CUSTOMREQUEST, CURLOPT_FOLLOWLOCATION, CURLOPT_FORBID_REUSE, CURLOPT_FRESH_CONNECT,
	CURLOPT_HEADER, CURLOPT_HEADERFUNCTION, CURLOPT_HTTP_VERSION, CURLOPT_HTTPHEADER, CURLOPT_INFILESIZE, CURLOPT_MAXREDIRS,
	CURLOPT_NOBODY, CURLOPT_POSTFIELDS, CURLOPT_PROTOCOLS, CURLOPT_READFUNCTION, CURLOPT_REDIR_PROTOCOLS, CURLOPT_RETURNTRANSFER,
	CURLOPT_SSL_VERIFYHOST, CURLOPT_SSL_VERIFYPEER, CURLOPT_SSL_VERIFYSTATUS, CURLOPT_TIMEOUT, CURLOPT_UPLOAD, CURLOPT_URL,
	CURLOPT_USERAGENT, CURLOPT_USERPWD, CURLOPT_WRITEFUNCTION, CURLPROTO_HTTP, CURLPROTO_HTTPS;
use const CURLOPT_DOH_URL;

/**
 * Implements a cURL connection object
 */
final class CurlHandle{

	public const CURL_NETWORK_ERRORS = [
		CURLE_COULDNT_RESOLVE_PROXY,
		CURLE_COULDNT_RESOLVE_HOST,
		CURLE_COULDNT_CONNECT,
		CURLE_OPERATION_TIMEOUTED,
		CURLE_SSL_CONNECT_ERROR,
		CURLE_GOT_NOTHING,
	];

	// these options shall not be overwritten
	private const NEVER_OVERWRITE = [
		CURLOPT_CAINFO,
		CURLOPT_CAPATH,
		CURLOPT_DOH_URL,
		CURLOPT_CUSTOMREQUEST,
		CURLOPT_HTTPHEADER,
		CURLOPT_NOBODY,
		CURLOPT_FORBID_REUSE,
		CURLOPT_FRESH_CONNECT,
	];

	private CH              $curl;
	private int             $handleID;
	private array           $curlOptions = [];
	private bool            $initialized = false;
	private StreamInterface $requestBody;
	private StreamInterface $responseBody;

	/**
	 * CurlHandle constructor.
	 */
	public function __construct(
		private RequestInterface                       $request,
		private ResponseInterface                      $response,
		private HTTPOptions|SettingsContainerInterface $options,
		StreamInterface|null                           $stream = null,
	){
		$this->curl         = curl_init();
		$this->handleID     = (int)$this->curl;
		$this->requestBody  = $this->request->getBody();
		$this->responseBody = ($stream ?? $this->response->getBody());
	}

	/**
	 * close an existing cURL handle on exit
	 */
	public function __destruct(){
		$this->close();
	}

	/**
	 * closes the handle
	 */
	public function close():self{
		curl_close($this->curl);

		return $this;
	}

	/**
	 * returns the internal cURL resource in its current state
	 *
	 * @codeCoverageIgnore
	 */
	public function getCurlResource():CH{
		return $this->curl;
	}

	/**
	 * returns the handle ID (cURL resource id)
	 *
	 * @codeCoverageIgnore
	 */
	public function getHandleID():int{
		return $this->handleID;
	}

	/**
	 * returns the result from `curl_getinfo()` or `null` in case of an error
	 *
	 * @see \curl_getinfo()
	 *
	 * @see https://www.php.net/manual/function.curl-getinfo.php#111678
	 * @see https://www.openssl.org/docs/manmaster/man1/verify.html#VERIFY_OPERATION
	 * @see https://github.com/openssl/openssl/blob/91cb81d40a8102c3d8667629661be8d6937db82b/include/openssl/x509_vfy.h#L99-L189
	 */
	public function getInfo():array|null{
		$info = curl_getinfo($this->curl);

		if($info !== false){
			return $info;
		}

		return null;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getRequest():RequestInterface{
		return $this->request;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getResponse():ResponseInterface{
		$this->responseBody->rewind();

		return $this->response->withBody($this->responseBody);
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getCurlOptions():array{
		return $this->curlOptions;
	}

	/**
	 * Check default locations for the CA bundle
	 *
	 * @see https://packages.ubuntu.com/search?suite=all&searchon=names&keywords=ca-certificates
	 * @see https://packages.debian.org/search?suite=all&searchon=names&keywords=ca-certificates
	 *
	 * @codeCoverageIgnore
	 * @throws \chillerlan\HTTP\ClientException
	 */
	private function guessCA():string{

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
			// https://www.jetbrains.com/help/idea/ssl-certificates.html
			'/etc/ssl/ca-bundle.pem',
			'/etc/pki/tls/cacert.pem',
			'/etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem',
			'/etc/ssl/cert.pem',
			// Windows?
			// http://php.net/manual/en/function.curl-setopt.php#110457
			'C:\\Windows\\system32\\curl-ca-bundle.crt',
			'C:\\Windows\\curl-ca-bundle.crt',
			'C:\\Windows\\system32\\cacert.pem',
			'C:\\Windows\\cacert.pem',
			// library path???
			__DIR__.'/cacert.pem',
		];

		foreach($cafiles as $file){

			if(file_exists($file) || (is_link($file) && file_exists(readlink($file)))){
				return $file;
			}

		}

		// still nothing???
		$msg = 'No system CA bundle could be found in any of the the common system locations. '
		       .'In order to verify peer certificates, you will need to supply the path on disk to a certificate '
		       .'bundle via HTTPOptions::$ca_info. If you do not need a specific certificate bundle, '
		       .'then you can download a CA bundle over here: https://curl.se/docs/caextract.html. '
		       .'Once you have a CA bundle available on disk, you can set the "curl.cainfo" php.ini setting to point '
		       .'to the path of the file, allowing you to omit the $ca_info setting. '
		       .'See https://curl.se/docs/sslcerts.html for more information.';

		throw new ClientException($msg);
	}

	/**
	 * @throws \chillerlan\HTTP\ClientException
	 */
	private function setCA():void{

		// early exit - nothing to do
		if(!$this->options->ssl_verifypeer){
			return;
		}

		$ca = $this->options->ca_info;

		if($ca === null){

			// check php.ini options - PHP should find the file by itself
			if(file_exists(ini_get('curl.cainfo'))){
				return;
			}

			// this is getting weird. as a last resort, we're going to check some default paths for a CA bundle file
			$ca = $this->guessCA();
		}

		$ca = trim($ca);

		// if you - for whatever obscure reason - need to check Windows .lnk links,
		// see http://php.net/manual/en/function.is-link.php#91249
		if(is_link($ca)){
			$ca = readlink($ca);
		}

		$ca = realpath($ca);

		if($ca !== false){

			if(is_file($ca)){
				$this->curlOptions[CURLOPT_CAINFO] = $ca;

				return;
			}

			if(is_dir($ca)){
				$this->curlOptions[CURLOPT_CAPATH] = $ca;

				return;
			}

		}

		throw new ClientException(sprintf('invalid path to SSL CA bundle: "%s"', $this->options->ca_info));
	}

	/**
	 * @throws \chillerlan\HTTP\ClientException
	 */
	private function setRequestOptions():void{
		$method   = strtoupper($this->request->getMethod());
		$userinfo = $this->request->getUri()->getUserInfo();
		$bodySize = $this->requestBody->getSize();

		if($method === ''){
			throw new ClientException('invalid HTTP method');
		}

		if(!empty($userinfo)){
			$this->curlOptions[CURLOPT_USERPWD] = $userinfo;
		}

		/*
		 * Some HTTP methods cannot have payload:
		 *
		 * - GET   — cURL will automatically change the method to PUT or POST
		 *           if we set CURLOPT_UPLOAD or CURLOPT_POSTFIELDS.
		 * - HEAD  — cURL treats HEAD as a GET request with same restrictions.
		 * - TRACE — According to RFC7231: a client MUST NOT send a message body in a TRACE request.
		 */
		if(in_array($method, ['DELETE', 'PATCH', 'POST', 'PUT'], true) && $bodySize > 0){

			if(!$this->request->hasHeader('Content-Length')){
				$this->request = $this->request->withHeader('Content-Length', (string)$bodySize);
			}

			if($this->requestBody->isSeekable()){
				$this->requestBody->rewind();
			}

			// Message has non-empty body.
			if($bodySize === null || $bodySize > (1 << 20)){
				// Avoid full loading large or unknown size body into memory
				$this->curlOptions[CURLOPT_UPLOAD] = true;

				if($bodySize !== null){
					$this->curlOptions[CURLOPT_INFILESIZE] = $bodySize;
				}

				$this->curlOptions[CURLOPT_READFUNCTION] = $this->readFunction(...);
			}
			// Small body can be loaded into memory
			else{
				$this->curlOptions[CURLOPT_POSTFIELDS] = (string)$this->requestBody;
			}

		}
		else{
			// Else if a body is not present, force "Content-length" to 0
			$this->request = $this->request->withHeader('Content-Length', '0');
		}

		// This will set HTTP method to "HEAD".
		if($method === 'HEAD'){
			$this->curlOptions[CURLOPT_NOBODY] = true;
		}

		// GET is a default method. Other methods should be specified explicitly.
		if($method !== 'GET'){
			$this->curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
		}

	}

	/**
	 * @see https://php.watch/articles/php-curl-security-hardening
	 */
	public function init():CH|null{

		$this->curlOptions = [
			CURLOPT_HEADER           => false,
			CURLOPT_HTTPHEADER       => [],
			CURLOPT_RETURNTRANSFER   => false,
			CURLOPT_FOLLOWLOCATION   => false,
			CURLOPT_MAXREDIRS        => 5,
			CURLOPT_URL              => (string)$this->request->getUri()->withFragment(''),
			CURLOPT_HTTP_VERSION     => CURL_HTTP_VERSION_2TLS,
			CURLOPT_USERAGENT        => $this->options->user_agent,
			CURLOPT_PROTOCOLS        => (CURLPROTO_HTTP | CURLPROTO_HTTPS),
			CURLOPT_REDIR_PROTOCOLS  => (CURLPROTO_HTTP | CURLPROTO_HTTPS),
			CURLOPT_TIMEOUT          => $this->options->timeout,
			CURLOPT_CONNECTTIMEOUT   => 30,
			CURLOPT_FORBID_REUSE     => true,
			CURLOPT_FRESH_CONNECT    => true,
			CURLOPT_HEADERFUNCTION   => $this->headerFunction(...),
			CURLOPT_WRITEFUNCTION    => $this->writeFunction(...),
			CURLOPT_SSL_VERIFYHOST   => 2,
			CURLOPT_SSL_VERIFYPEER   => $this->options->ssl_verifypeer,
			CURLOPT_SSL_VERIFYSTATUS => ($this->options->ssl_verifypeer && $this->options->curl_check_OCSP),
			CURLOPT_DOH_URL          => $this->options->dns_over_https,

			// PHP 8.2+
#			CURLOPT_DOH_SSL_VERIFYHOST, CURLOPT_DOH_SSL_VERIFYPEER, CURLOPT_DOH_SSL_VERIFYSTATUS, CURLOPT_CAINFO_BLOB
		];

		$this->setCA();
		$this->setRequestOptions();

		// curl-client does not support "Expect-Continue", so dropping "expect" headers
		$headers = HeaderUtil::normalize($this->request->withoutHeader('Expect')->getHeaders());

		foreach($headers as $name => $value){
			// cURL requires a special format for empty headers.
			// See https://github.com/guzzle/guzzle/issues/1882 for more details.
			$this->curlOptions[CURLOPT_HTTPHEADER][] = ($value === '') ? $name.';' : $name.': '.$value;
		}

		// If the Expect header is not present (it isn't), prevent curl from adding it
		$this->curlOptions[CURLOPT_HTTPHEADER][] = 'Expect:';

		// cURL sometimes adds a content-type by default. Prevent this.
		if(!$this->request->hasHeader('Content-Type')){
			$this->curlOptions[CURLOPT_HTTPHEADER][] = 'Content-Type:';
		}

		// overwrite the default values with $curl_options
		foreach($this->options->curl_options as $k => $v){
			// skip some options that are only set automatically or shall not be overwritten
			if(in_array($k, $this::NEVER_OVERWRITE, true)){
				continue;
			}

			$this->curlOptions[$k] = $v;
		}

		curl_setopt_array($this->curl, $this->curlOptions);

		$this->initialized = true;

		return $this->curl;
	}

	/**
	 * executes the current cURL instance and returns the error number
	 *
	 * @see \curl_exec()
	 * @see \curl_errno()
	 */
	public function exec():int{

		if(!$this->initialized){
			$this->init();
		}

		curl_exec($this->curl);

		return curl_errno($this->curl);
	}

	/**
	 * returns a string containing the last error
	 *
	 * @see \curl_error()
	 */
	public function getError():string{
		return curl_error($this->curl);
	}

	/**
	 * A callback accepting three parameters. The first is the cURL resource, the second is a stream resource
	 * provided to cURL through the option CURLOPT_INFILE, and the third is the maximum amount of data to be read.
	 * The callback must return a string with a length equal or smaller than the amount of data requested,
	 * typically by reading it from the passed stream resource. It should return an empty string to signal EOF.
	 *
	 * @see https://www.php.net/manual/function.curl-setopt
	 *
	 * @internal
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function readFunction(CH $curl, $stream, int $length):string{
		return $this->requestBody->read($length);
	}

	/**
	 * A callback accepting two parameters. The first is the cURL resource, and the second is a string
	 * with the data to be written. The data must be saved by this callback. It must return the exact
	 * number of bytes written or the transfer will be aborted with an error.
	 *
	 * @see https://www.php.net/manual/function.curl-setopt
	 *
	 * @internal
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function writeFunction(CH $curl, string $data):int{
		return $this->responseBody->write($data);
	}

	/**
	 * A callback accepting two parameters. The first is the cURL resource, the second is a string with the header
	 * data to be written. The header data must be written by this callback. Return the number of bytes written.
	 *
	 * @see https://www.php.net/manual/function.curl-setopt
	 *
	 * @internal
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function headerFunction(CH $curl, string $line):int{
		$header = explode(':', trim($line), 2);

		if(count($header) === 2){
			$this->response = $this->response->withAddedHeader(trim($header[0]), trim($header[1]));
		}
		elseif(str_starts_with(strtoupper($header[0]), 'HTTP/')){
			$status = explode(' ', $header[0], 3);
			$reason = (count($status) > 2) ? trim($status[2]) : '';

			$this->response = $this->response
				->withStatus((int)$status[1], $reason)
				->withProtocolVersion(substr($status[0], 5))
			;
		}

		return strlen($line);
	}

}
