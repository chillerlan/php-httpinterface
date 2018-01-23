<?php
/**
 * Class HTTPClientAbstract
 *
 * @filesource   HTTPClientAbstract.php
 * @created      09.07.2017
 * @package      chillerlan\HTTP
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2017 Smiley
 * @license      MIT
 */

namespace chillerlan\HTTP;

use chillerlan\Traits\ContainerInterface;

abstract class HTTPClientAbstract implements HTTPClientInterface{

	/**
	 * @var mixed
	 */
	protected $http;

	/**
	 * @var \chillerlan\Traits\ContainerInterface|mixed
	 */
	protected $options;

	/** @inheritdoc */
	public function __construct(ContainerInterface $options){
		$this->options = $options;
	}

	/** @inheritdoc */
	public function normalizeRequestHeaders(array $headers):array {
		$normalized_headers = [];

		foreach($headers as $key => $val){

			if(is_numeric($key)){
				$header = explode(':', $val, 2);

				if(count($header) !== 2){
					continue;
				}

				$key = $header[0];
				$val = $header[1];
			}

			$key = ucfirst(strtolower($key));

			$normalized_headers[$key] = trim($key).': '.trim($val);
		}

		return $normalized_headers;
	}


}
