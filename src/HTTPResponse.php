<?php
/**
 * Class HTTPResponse
 *
 * @filesource   HTTPResponse.php
 * @created      09.07.2017
 * @package      chillerlan\HTTP
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

use chillerlan\Traits\{
	ImmutableSettingsContainer, ImmutableSettingsInterface
};

class HTTPResponse implements HTTPResponseInterface, ImmutableSettingsInterface{
	use ImmutableSettingsContainer{
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
	 * @todo
	 *
	 * @param string $property
	 *
	 * @return null|mixed
	 */
	public function __get(string $property){

		switch($property){
			case 'json':       return json_decode($this->body);
			case 'json_array': return json_decode($this->body, true);
			case 'xml':        return simplexml_load_string($this->body);
			case 'xml_array':  return json_decode(json_encode(simplexml_load_string($this->body)), true); // cruel, but works.
		}

		return $this->containerGet($property);
	}


}
