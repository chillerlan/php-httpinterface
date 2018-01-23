<?php
/**
 * Trait HTTPOptions
 *
 * @filesource   HTTPOptions.php
 * @created      23.01.2018
 * @package      chillerlan\HTTP
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2018 Smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

/**
 */
trait HTTPOptions{

	/**
	 * @var string
	 */
	public $user_agent = 'chillerlanPhpHTTP/1.0 +https://github.com/chillerlan/php-httpinterface';

	/**
	 * @var int
	 */
	public $timeout = 10;

	/**
	 * options for each curl instance
	 *
	 * @var array
	 */
	public $curl_options = [];

	/**
	 * CA Root Certificates for use with CURL/SSL
	 *
	 * @var string
	 * @link https://curl.haxx.se/ca/cacert.pem
	 */
	public $ca_info = null;

	/**
	 * @var int
	 */
	public $max_redirects = 0;

}
