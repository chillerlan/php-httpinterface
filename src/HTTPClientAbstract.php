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

use chillerlan\Traits\ImmutableSettingsInterface;
use Psr\Log\{
	LoggerAwareInterface, LoggerAwareTrait, LoggerInterface, NullLogger
};

abstract class HTTPClientAbstract implements HTTPClientInterface, LoggerAwareInterface{
	use LoggerAwareTrait;

	/**
	 * @var mixed
	 */
	protected $http;

	/**
	 * @var \chillerlan\Traits\ImmutableSettingsInterface|mixed
	 */
	protected $options;

	protected $requestURL;
	protected $parsedURL;
	protected $requestParams;
	protected $requestMethod;
	protected $requestBody;
	protected $requestHeaders;

	/** @inheritdoc */
	public function __construct(ImmutableSettingsInterface $options, LoggerInterface $logger = null){
		$this->options = $options;
		$this->logger  = $logger ?? new NullLogger;
	}

	/**
	 * @return \chillerlan\HTTP\HTTPResponseInterface
	 */
	abstract protected function getResponse():HTTPResponseInterface;

	/**
	 * @param string      $url
	 * @param array|null  $params
	 * @param string|null $method
	 * @param null        $body
	 * @param array|null  $headers
	 *
	 * @return \chillerlan\HTTP\HTTPResponseInterface
	 * @throws \chillerlan\HTTP\HTTPClientException
	 */
	public function request(string $url, array $params = null, string $method = null, $body = null, array $headers = null):HTTPResponseInterface{
		$this->requestURL    = $url;
		$this->parsedURL     = parse_url($this->requestURL);
		$this->requestParams = $params ?? [];
		$this->requestMethod = strtoupper($method ?? 'POST');
		$this->requestBody   = $body;
		$this->requestHeaders = $headers ?? [];

		if(!isset($this->parsedURL['host']) || !in_array($this->parsedURL['scheme'], $this::ALLOWED_SCHEMES, true)){
			throw new HTTPClientException('invalid URL');
		}

		try{
			return $this->getResponse();
		}
		catch(\Exception $e){
			throw new HTTPClientException('fetch error: '.$e->getMessage());
		}

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

	/** @inheritdoc */
	public function rawurlencode($data){

		if(is_array($data)){
			return array_map([$this, 'rawurlencode'], $data);
		}
		elseif(is_scalar($data)){
			return rawurlencode($data);
		}

		return $data; // @codeCoverageIgnore
	}

	/**
	 * from https://github.com/abraham/twitteroauth/blob/master/src/Util.php
	 *
	 * @param array  $params
	 * @param bool   $urlencode
	 * @param string $delimiter
	 * @param string $enclosure
	 *
	 * @return string
	 */
	public function buildQuery(array $params, bool $urlencode = null, string $delimiter = null, string $enclosure = null):string {

		if(empty($params)) {
			return '';
		}

		// urlencode both keys and values
		if($urlencode ?? true){
			$params = array_combine(
				$this->rawurlencode(array_keys($params)),
				$this->rawurlencode(array_values($params))
			);
		}

		// Parameters are sorted by name, using lexicographical byte value ordering.
		// Ref: Spec: 9.1.1 (1)
		uksort($params, 'strcmp');

		$pairs     = [];
		$enclosure = $enclosure ?? '';

		foreach($params as $parameter => $value){

			if(is_array($value)) {
				// If two or more parameters share the same name, they are sorted by their value
				// Ref: Spec: 9.1.1 (1)
				// June 12th, 2010 - changed to sort because of issue 164 by hidetaka
				sort($value, SORT_STRING);

				foreach ($value as $duplicateValue) {
					$pairs[] = $parameter.'='.$enclosure.$duplicateValue.$enclosure;
				}

			}
			else{
				$pairs[] = $parameter.'='.$enclosure.$value.$enclosure;
			}

		}

		// For each parameter, the name is separated from the corresponding value by an '=' character (ASCII code 61)
		// Each name-value pair is separated by an '&' character (ASCII code 38)
		return implode($delimiter ?? '&', $pairs);
	}

	/** @inheritdoc */
	public function checkQueryParams(array $params, bool $booleans_as_string = null):array{

		foreach($params as $key => $value){

			if(is_bool($value)){
				$params[$key] = $booleans_as_string === true
					? ($value ? 'true' : 'false')
					: (string)(int)$value;
			}
			elseif(is_null($value) || empty($value)){
				unset($params[$key]);
			}

		}

		return $params;
	}

}
