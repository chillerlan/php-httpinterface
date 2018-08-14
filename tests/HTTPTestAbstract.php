<?php
/**
 * Class HTTPTestAbstract
 *
 * @filesource   HTTPTestAbstract.php
 * @created      12.08.2018
 * @package      chillerlan\HTTPTest
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\HTTPFactory;
use PHPUnit\Framework\TestCase;

abstract class HTTPTestAbstract extends TestCase{

	/**
	 * @var \chillerlan\HTTP\HTTPFactory
	 */
	protected $factory;

	protected function setUp(){
		$this->factory = new HTTPFactory;
	}

}
