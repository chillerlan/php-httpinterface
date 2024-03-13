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

declare(strict_types=1);

namespace chillerlan\HTTP;

use function trim;
use const CURLOPT_CAINFO, CURLOPT_CAPATH;

/**
 *
 */
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
	 * CA Root Certificates for use with CURL/SSL
	 *
	 * (if not configured in php.ini or available in a default path via the `ca-certificates` package)
	 *
	 * @link https://curl.se/docs/caextract.html
	 * @link https://curl.se/ca/cacert.pem
	 * @link https://raw.githubusercontent.com/bagder/ca-bundle/master/ca-bundle.crt
	 */
	protected string|null $ca_info = null;

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
	 * maximum of concurrent requests for curl_multi
	 */
	protected int $window_size = 5;

	/**
	 * sleep timer (microseconds) between each fired multi request on startup
	 */
	protected int $sleep = 0;

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
	 * Sets a DNS-over-HTTPS provider URL
	 *
	 * e.g.
	 *
	 *   - https://cloudflare-dns.com/dns-query
	 *   - https://dns.google/dns-query
	 *   - https://dns.nextdns.io
	 *
	 * @see https://en.wikipedia.org/wiki/DNS_over_HTTPS
	 * @see https://github.com/curl/curl/wiki/DNS-over-HTTPS
	 */
	protected string|null $dns_over_https = null;

	/**
	 * @throws \Psr\Http\Client\ClientExceptionInterface
	 */
	protected function set_user_agent(string $user_agent):void{
		$user_agent = trim($user_agent);

		if(empty($user_agent)){
			throw new ClientException('invalid user agent');
		}

		$this->user_agent = $user_agent;
	}

	/**
	 *
	 */
	protected function set_curl_options(array $curl_options):void{

		// let's check if there's a CA bundle given via the cURL options and move it to the ca_info option instead
		foreach([CURLOPT_CAINFO, CURLOPT_CAPATH] as $opt){

			if(!empty($curl_options[$opt])){

				if($this->ca_info === null){
					$this->ca_info = $curl_options[$opt];
				}

				unset($curl_options[$opt]);
			}
		}

		$this->curl_options = $curl_options;
	}

	protected function set_dns_over_https(string|null $dns_over_https):void{
		$this->dns_over_https = null;

		if($dns_over_https !== null){
			$dns_over_https = trim($dns_over_https);

			if(!empty($dns_over_https)){
				$this->dns_over_https = $dns_over_https;
			}
		}

	}
}
