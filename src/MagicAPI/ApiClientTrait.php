<?php
/**
 * Trait ApiClientTrait
 *
 * @filesource   ApiClientTrait.php
 * @created      07.04.2018
 * @package      chillerlan\HTTP\MagicAPI
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\MagicAPI;

use chillerlan\HTTP\Psr7;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * @link http://php.net/manual/language.oop5.magic.php#118617
 *
 * @implements chillerlan\MagicAPI\ApiClientInterface
 *
 * from \chillerlan\HTTP\HTTPClientInterface:
 * @method request(string $url, string $method = null, array $params = null, $body = null, array $headers = null):ResponseInterface
 */
trait ApiClientTrait{

	/**
	 * The logger instance.
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * @var \chillerlan\HTTP\MagicAPI\EndpointMapInterface
	 *
	 * method => [url, method, mandatory_params, params_in_url, ...]
	 */
	protected $endpoints;

	/**
	 * @param string $endpointMap
	 *
	 * @return \chillerlan\HTTP\MagicAPI\ApiClientInterface
	 * @throws \chillerlan\HTTP\MagicAPI\ApiClientException
	 */
	public function loadEndpoints(string $endpointMap):ApiClientInterface{

		if(class_exists($endpointMap)){
			$this->endpoints = new $endpointMap;

			if(!$this->endpoints instanceof EndpointMapInterface){
				throw new ApiClientException('invalid endpoint map');
			}
		}

		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return $this;
	}

	/**
	 * ugly, isn't it?
	 * @todo WIP
	 *
	 * @param string $name
	 * @param array  $arguments
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 * @throws \chillerlan\HTTP\MagicAPI\APIClientException
	 */
	public function __call(string $name, array $arguments):ResponseInterface{

		if(!$this->endpoints instanceof EndpointMapInterface || !$this->endpoints->__isset($name)){
			throw new ApiClientException('endpoint not found');
		}

		$m = $this->endpoints->{$name};

		$endpoint      = $this->endpoints->API_BASE.$m['path'];
		$method        = $m['method'] ?? 'GET';
		$body          = null;
		$headers       = isset($m['headers']) && is_array($m['headers']) ? $m['headers'] : [];
		$path_elements = $m['path_elements'] ?? [];
		$params_in_url = count($path_elements);
		$params        = $arguments[$params_in_url] ?? [];
		$urlparams     = array_slice($arguments, 0, $params_in_url);

		if($params_in_url > 0){

			if(count($urlparams) < $params_in_url){
				throw new APIClientException('too few URL params, required: '.implode(', ', $path_elements));
			}

			$endpoint = sprintf($endpoint, ...$urlparams);
		}

		if(in_array($method, ['DELETE', 'POST', 'PATCH', 'PUT'], true)){
			$body = $arguments[$params_in_url + 1] ?? $params;

			if($params === $body){
				$params = [];
			}

			if(is_iterable($body)){
				$body = Psr7\clean_query_params($body);
			}

		}

		$params = Psr7\clean_query_params($params);

		if($this->logger instanceof LoggerInterface){

			$this->logger->debug(get_called_class().'::__call() -> '.(new ReflectionClass($this))->getShortName().'::'.$name.'()', [
				'$endpoint' => $endpoint, '$method' => $method, '$params' => $params, '$body' => $body, '$headers' => $headers,
			]);

		}

		return $this->request($endpoint, $method, $params, $body, $headers);
	}

}
