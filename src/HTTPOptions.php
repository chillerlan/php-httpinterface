<?php
/**
 * Class HTTPOptions
 *
 * @created      28.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\HTTP;

use chillerlan\Settings\SettingsContainerAbstract;

/**
 *
 */
class HTTPOptions extends SettingsContainerAbstract{
	use HTTPOptionsTrait;
}
