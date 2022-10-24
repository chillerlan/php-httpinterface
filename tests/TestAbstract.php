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
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{
	RequestFactoryInterface, ResponseFactoryInterface, ServerRequestFactoryInterface,
	StreamFactoryInterface, UploadedFileFactoryInterface, UriFactoryInterface
};
use function constant, defined;

abstract class TestAbstract extends TestCase{

	protected const FACTORIES = [
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

		foreach($this::FACTORIES as $property => $const){

			if(!defined($const)){
				throw new Exception('constant "'.$const.'" not defined -> see phpunit.xml');
			}

			$class             = constant($const);
			$this->{$property} = new $class;
		}

		$this->server = new ServerUtil(
			$this->serverRequestFactory,
			$this->uriFactory,
			$this->uploadedFileFactory,
			$this->streamFactory
		);
	}


}
