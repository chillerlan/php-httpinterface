<?php
/**
 * Trait HTTPOptionsTrait
 *
 * @created      28.08.2018
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2018 Smiley
 * @license      MIT
 */
declare(strict_types=1);

namespace chillerlan\HTTP;

use function parse_url, sprintf, strtolower, trim;
use const CURLOPT_CAINFO, CURLOPT_CAINFO_BLOB, CURLOPT_CAPATH;

trait HTTPOptionsTrait{

	/**
	 * A custom user agent string
	 */
	public string $user_agent = 'chillerlanHttpInterface/6.0 +https://github.com/chillerlan/php-httpinterface' {
		/**
		 * @throws \chillerlan\HTTP\ClientException
		 */
		set{
			$value = trim($value);

			if(empty($value)){
				throw new ClientException('invalid user agent');
			}

			$this->user_agent = $value;
		}
	}

	/**
	 * options for each curl instance
	 *
	 * this array is being merged into the default options as the last thing before curl_exec().
	 * none of the values (except existence of the CA file) will be checked - that's up to the implementation.
	 *
	 * @var array<int, mixed>
	 */
	public array $curl_options = [] {
		set{
			// let's check if there's a CA bundle given via the cURL options and move it to the ca_info option instead
			foreach([CURLOPT_CAINFO, CURLOPT_CAINFO_BLOB, CURLOPT_CAPATH] as $opt){

				if(!empty($value[$opt])){

					if($this->ca_info === null){
						$this->ca_info = $value[$opt];
					}

					unset($value[$opt]);
				}
			}

			$this->curl_options = $value;
		}
	}

	/**
	 * CA Root Certificates for use with CURL/SSL
	 *
	 * (if not configured in php.ini or available in a default path via the `ca-certificates` package)
	 *
	 * Plese note: if you also set CURLOPT_CAINFO or CURLOPT_CAPATH in the `$curl_options` array,
	 *             these will take precedence and overwrite the value here.
	 *
	 * @see https://curl.se/docs/caextract.html
	 * @see https://curl.se/ca/cacert.pem
	 * @see https://raw.githubusercontent.com/bagder/ca-bundle/master/ca-bundle.crt
	 */
	public string|null $ca_info = null;

	/**
	 * requires either HTTPOptions::$ca_info or a properly working system CA file
	 *
	 * @see \CURLOPT_SSL_VERIFYPEER
	 * @see https://php.net/manual/function.curl-setopt.php
	 */
	public bool $ssl_verifypeer = true;

	/**
	 * requires either HTTPOptions::$ca_info or a properly working system CA file
	 *
	 * @see \CURLOPT_DOH_SSL_VERIFYPEER
	 * @see https://php.net/manual/function.curl-setopt.php
	 */
	public bool $ssl_doh_verifypeer = true;

	/**
	 * options for the curl multi instance
	 *
	 * @see https://www.php.net/manual/function.curl-multi-setopt.php
	 *
	 * @var array<int, mixed>
	 */
	public array $curl_multi_options = [];

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
	public bool $curl_check_OCSP = false;

	/**
	 * When set to true, cURL validates that the DoH server staples an OCSP response during the TLS handshake.
	 *
	 * @see \CURLOPT_DOH_SSL_VERIFYSTATUS
	 */
	public bool $curl_check_doh_OCSP = false;

	/**
	 * maximum of concurrent requests for curl_multi
	 */
	public int $window_size = 5;

	/**
	 * sleep timer (microseconds) between each fired multi request on startup
	 */
	public int $sleep = 0;

	/**
	 * Timeout value
	 *
	 * @see \CURLOPT_TIMEOUT
	 */
	public int $timeout = 10;

	/**
	 * Number of retries (multi fetch)
	 */
	public int $retries = 3;

	/**
	 * Sets a DNS-over-HTTPS provider URL
	 *
	 * e.g.
	 *
	 *   - https://cloudflare-dns.com/dns-query
	 *   - https://dns.google/dns-query
	 *   - https://dns.nextdns.io
	 *
	 * @see \CURLOPT_DOH_URL
	 * @see https://en.wikipedia.org/wiki/DNS_over_HTTPS
	 * @see https://github.com/curl/curl/wiki/DNS-over-HTTPS
	 */
	public string|null $dns_over_https = null {
		/**
		 * @throws \chillerlan\HTTP\ClientException
		 */
		set(string|null $doh){ // phpcs:ignore

			if($doh === null){
				$this->dns_over_https = null;

				return;
			}

			$doh    = trim($doh);
			$parsed = parse_url($doh);

			if($doh === '' || !isset($parsed['scheme'], $parsed['host']) || strtolower($parsed['scheme']) !== 'https'){
				throw new ClientException(sprintf('invalid DNS-over-HTTPS URL: "%s"', $doh));
			}

			$this->dns_over_https = $doh;
		}
	}

}
