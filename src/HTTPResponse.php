<?php
/**
 * Class HTTPResponse
 *
 * @filesource   HTTPResponsephp
 * @created      09.07.2017
 * @package      chillerlan\HTTP
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

use chillerlan\Traits\{
	Container, ContainerInterface
};

class HTTPResponse implements HTTPResponseInterface, ContainerInterface{
	use Container{
		__get as containerGet;
	}

	/**
	 * @var string
	 */
	protected $url;

	/**
	 * @var \stdClass
	 */
	protected $headers;

	/**
	 * @var string
	 */
	protected $body;

	/**
	 * @codeCoverageIgnore
	 *
	 * @param string $property
	 *
	 * @return null|mixed
	 */
	public function __get(string $property){

		if($property === 'json'){
			return json_decode($this->body);
		}
		elseif($property === 'json_array'){
			return json_decode($this->body, true);
		}

		return $this->containerGet($property);
	}


}
