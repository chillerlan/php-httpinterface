<?php
/**
 * Class TestAbstract
 *
 * @created      29.03.2021
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2021 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest;

use chillerlan\HTTP\Utils\ServerUtil;
use Exception;
use Psr\Http\Message\{
	RequestFactoryInterface, ResponseFactoryInterface, ServerRequestFactoryInterface,
	StreamFactoryInterface, UploadedFileFactoryInterface, UriFactoryInterface
};
use function class_exists;
use function constant;
use function defined;
use function method_exists;
use function sprintf;

trait FactoryTrait{

	private array $FACTORIES = [
		'requestFactory'       => 'REQUEST_FACTORY',
		'responseFactory'      => 'RESPONSE_FACTORY',
		'serverRequestFactory' => 'SERVER_REQUEST_FACTORY',
		'streamFactory'        => 'STREAM_FACTORY',
		'uploadedFileFactory'  => 'UPLOADED_FILE_FACTORY',
		'uriFactory'           => 'URI_FACTORY',
	];

	protected RequestFactoryInterface $requestFactory;
	protected ResponseFactoryInterface $responseFactory;
	protected ServerRequestFactoryInterface $serverRequestFactory;
	protected StreamFactoryInterface $streamFactory;
	protected UploadedFileFactoryInterface $uploadedFileFactory;
	protected UriFactoryInterface $uriFactory;
	protected ServerUtil $server;

	/**
	 * @throws \Exception
	 */
	protected function setUp():void{

		foreach($this->FACTORIES as $property => $const){

			if(!defined($const)){
				throw new Exception(sprintf('constant "%s" not defined -> see phpunit.xml', $const));
			}

			$class = constant($const);

			if(!class_exists($class)){
				throw new Exception(sprintf('invalid class: "%s"', $class));
			}

			$this->{$property} = new $class;
		}

		$this->server = new ServerUtil(
			$this->serverRequestFactory,
			$this->uriFactory,
			$this->uploadedFileFactory,
			$this->streamFactory
		);

		if(method_exists($this, '__setUp')){
			$this->__setUp();
		}

	}

}
