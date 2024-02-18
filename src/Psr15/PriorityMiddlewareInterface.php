<?php
/**
 * Interface PriorityMiddlewareInterface
 *
 * @created      10.03.2019
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr15;

use Psr\Http\Server\MiddlewareInterface;

/**
 *
 */
interface PriorityMiddlewareInterface extends MiddlewareInterface{

	/**
	 *
	 */
	public function getPriority():int;

}
