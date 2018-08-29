<?php
/**
 * @filesource   includes.php
 * @created      28.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

// @codeCoverageIgnoreStart
if(!defined('PSR7_INCLUDES')){
	require_once __DIR__.'/Psr7/message_helpers.php';
}

if(!defined('PSR17_INCLUDES')){
	require_once __DIR__.'/Psr17/factory_helpers.php';
}
// @codeCoverageIgnoreEnd
