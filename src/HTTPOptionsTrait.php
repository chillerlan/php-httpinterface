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
	 * CA Root Certificates for use with CURL/SSL (if not configured in php.ini)
	 *
	 * @var string
	 * @link https://curl.haxx.se/ca/cacert.pem
	 * @link https://raw.githubusercontent.com/bagder/ca-bundle/master/ca-bundle.crt
	 */
	public $ca_info = null;

	/**
	 * @var int
	 */
	public $max_redirects = 0;

}
