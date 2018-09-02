<?php
/**
 * Class EndpointMap
 *
 * @filesource   EndpointMap.php
 * @created      01.09.2018
 * @package      chillerlan\HTTP\MagicAPI
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\MagicAPI;

use chillerlan\Settings\SettingsContainerAbstract;

abstract class EndpointMap extends SettingsContainerAbstract implements EndpointMapInterface{

	protected $API_BASE = '';

}
