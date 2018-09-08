<?php
/**
 * Class EndpointDocblock
 *
 * @filesource   EndpointDocblock.php
 * @created      08.09.2018
 * @package      chillerlan\HTTP\MagicAPI
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\MagicAPI;

use ReflectionClass;

class EndpointDocblock{

	/**
	 * @var object
	 */
	protected $provider;

	/**
	 * @var \chillerlan\HTTP\MagicAPI\EndpointMapInterface
	 */
	protected $endpointMap;

	/**
	 * EndpointDocblock constructor.
	 *
	 * @param                                                $provider
	 * @param \chillerlan\HTTP\MagicAPI\EndpointMapInterface $endpointMap
	 */
	public function __construct($provider, EndpointMapInterface $endpointMap){
		$this->provider    = $provider;
		$this->endpointMap = $endpointMap;
	}

	/**
	 * @param string $returntype
	 *
	 * @return string
	 * @throws \chillerlan\HTTP\MagicAPI\ApiClientException
	 */
	public function create(string $returntype):string{

		if(!$this->endpointMap instanceof EndpointMap){
			throw new ApiClientException('invalid endpoint map');
		}

		$str = '/**'.PHP_EOL;

		$ep = $this->endpointMap->toArray();
		ksort($ep);

		foreach($ep as $methodName => $params){

			if($methodName === 'API_BASE'){
				continue;
			}

			$args = [];

			if(isset($params['path_elements']) && count($params['path_elements']) > 0){

				foreach($params['path_elements'] as $i){
					$args[] = 'string $'.$i;
				}

			}

			if(isset($params['query']) && !empty($params['query'])){
				$args[] = 'array $params = [\''.implode('\', \'', $params['query']).'\']';
			}

			if(isset($params['method']) && in_array($params['method'], ['PATCH', 'POST', 'PUT', 'DELETE'], true)){

				if($params['body'] !== null){
					$args[] = is_array($params['body']) ? 'array $body = [\''.implode('\', \'', $params['body']).'\']' : 'array $body = []';
				}

			}

			$str.= ' * @method \\'.$returntype.' '.$methodName.'('.implode(', ', $args).')'.PHP_EOL;
		}

		$str .= ' *'.'/'.PHP_EOL;

		$reflection = new ReflectionClass($this->provider);
		$classfile  = $reflection->getFileName();

		file_put_contents($classfile, str_replace($reflection->getDocComment().PHP_EOL, $str, file_get_contents($classfile)));

		return $str;
	}

	/**
	 * @param string $name
	 * @param string $returntype
	 *
	 * @return bool
	 */
	public function createInterface(string $name, string $returntype):bool{
		$interfaceName = $name.'Interface';

		$str = '<?php'.PHP_EOL.PHP_EOL
		       .'namespace '.__NAMESPACE__.';'.PHP_EOL.PHP_EOL
		       .'use \\'.$returntype.';'.PHP_EOL.PHP_EOL
		       .'interface '.$interfaceName.'{'.PHP_EOL.PHP_EOL;

		$ep = $this->endpointMap->toArray();
		ksort($ep);

		foreach($ep as $methodName => $params){

			if($methodName === 'API_BASE'){
				continue;
			}

			$args = [];
			$str.= "\t".'/**'.PHP_EOL;

			if(is_array($params['path_elements']) && count($params['path_elements']) > 0){

				foreach($params['path_elements'] as $i){
					$a = 'string $'.$i;
					$str.= "\t".' * @param '.$a.PHP_EOL;

					$args[] = $a;
				}

			}

			if(!empty($params['query'])){
				$a = 'array $params = [\''.implode('\', \'', $params['query']).'\']';
				$str.= "\t".' * @param '.$a.PHP_EOL;
				$args[] = $a;
			}

			if(in_array($params['method'], ['PATCH', 'POST', 'PUT', 'DELETE'])){

				if($params['body'] !== null){
					$a = is_array($params['body']) ? 'array $body = [\''.implode('\', \'', $params['body']).'\']' : 'array $body = []';
					$str.= "\t".' * @param '.$a.PHP_EOL;
					$args[] = $a;
				}

			}

			$r = new ReflectionClass($returntype);

			$str.= "\t".' * @return \\'.$r->getName().PHP_EOL;
			$str.= "\t".' */'.PHP_EOL;
			$str.= "\t".'public function '.$methodName.'('.implode(', ', $args).'):'.$r->getShortName().';'.PHP_EOL.PHP_EOL;
		}

		$str .= '}'.PHP_EOL;

		return (bool)file_put_contents(dirname((new ReflectionClass($this->provider))->getFileName()).'/'.$interfaceName.'.php', $str);
	}

}
