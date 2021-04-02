<?php
/**
 * Class HTTPOptions
 *
 * @created      28.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

use chillerlan\Settings\SettingsContainerAbstract;

/**
 * @property string    $user_agent
 * @property array     $curl_options
 * @property string    $ca_info
 * @property bool      $ssl_verifypeer
 * @property int       $window_size
 * @property int|float $sleep
 * @property int       $timeout
 * @property int       $retries
 * @property array     $curl_multi_options
 * @property bool      $curl_check_OCSP
 */
class HTTPOptions extends SettingsContainerAbstract{
	use HTTPOptionsTrait;
}
