<?php
/**
 * Class HTTPOptions
 *
 * @filesource   HTTPOptions.php
 * @created      28.08.2018
 * @package      chillerlan\HTTP
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

use chillerlan\Settings\SettingsContainerAbstract;

/**
 * @property string $user_agent
 * @property array  $curl_options
 * @property string $ca_info
 */
class HTTPOptions extends SettingsContainerAbstract{
	use HTTPOptionsTrait;
}