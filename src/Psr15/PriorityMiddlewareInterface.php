<?php
/**
 * Interface PriorityMiddlewareInterface
 *
 * @filesource   PriorityMiddlewareInterface.php
 * @created      10.03.2019
 * @package      chillerlan\HTTP\Psr15
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr15;

use Psr\Http\Server\MiddlewareInterface;

interface PriorityMiddlewareInterface extends MiddlewareInterface{

	/**
	 * @return int
	 */
	public function getPriority():int;

}
