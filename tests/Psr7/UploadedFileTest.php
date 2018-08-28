<?php
/**
 * Class UploadedFileTest
 *
 * @link https://github.com/guzzle/psr7/blob/4b981cdeb8c13d22a6c193554f8c686f53d5c958/tests/UploadedFileTest.php
 *
 * @filesource   UploadedFileTest.php
 * @created      12.08.2018
 * @package      chillerlan\HTTPTest\Psr7
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTPTest\Psr7;

use chillerlan\HTTP\Psr7;
use chillerlan\HTTP\Psr7\UploadedFile;
use chillerlan\HTTP\Psr17\{StreamFactory, UploadedFileFactory};
use PHPUnit\Framework\TestCase;

class UploadedFileTest extends TestCase{

	/**
	 * @var array
	 */
	protected $cleanup;

	/**
	 * @var \chillerlan\HTTP\Psr17\UploadedFileFactory
	 */
	protected $uploadedFileFactory;

	/**
	 * @var \chillerlan\HTTP\Psr17\StreamFactory
	 */
	protected $streamFactory;

	protected function setUp(){
		$this->cleanup = [];
		$this->uploadedFileFactory = new UploadedFileFactory;
		$this->streamFactory       = new StreamFactory;
	}

	protected function tearDown(){
		foreach($this->cleanup as $file){
			if(is_scalar($file) && file_exists($file)){
				unlink($file);
			}
		}
	}

	public function invalidStreams(){
		return [
#			'null'   => [null],
#			'true'   => [true],
#			'false'  => [false],
#			'int'    => [1],
#			'float'  => [1.1],
			'array'  => [['filename']],
			'object' => [(object)['filename']],
		];
	}

	/**
	 * @dataProvider invalidStreams
	 * @expectedException \InvalidArgumentException
	 *
	 * @param $streamOrFile
	 */
	public function testRaisesExceptionOnInvalidStreamOrFile($streamOrFile){
		new UploadedFile($streamOrFile, 0, UPLOAD_ERR_OK);
	}

	public function invalidErrorStatuses(){
		return [
			'negative' => [-1],
			'too-big'  => [9],
		];
	}

	/**
	 * @dataProvider invalidErrorStatuses
	 * @expectedException \InvalidArgumentException
	 *
	 * @param int $status
	 */
	public function testRaisesExceptionOnInvalidErrorStatus(int $status){
		new UploadedFile(fopen('php://temp', 'wb+'), 0, $status);
	}

	public function testGetStreamReturnsOriginalStreamObject(){
		$stream = $this->streamFactory->createStream('');
		$upload = $this->uploadedFileFactory->createUploadedFile($stream, 0, UPLOAD_ERR_OK); // coverage

		$this->assertSame($stream, $upload->getStream());
	}

	public function testGetStreamReturnsWrappedPhpStream(){
		$stream       = fopen('php://temp', 'wb+');
		$upload       = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
		$uploadStream = $upload->getStream()->detach();

		$this->assertSame($stream, $uploadStream);
	}

	public function testSuccessful(){
		$stream = $this->streamFactory->createStream('Foo bar!');
		$upload = new UploadedFile($stream, $stream->getSize(), UPLOAD_ERR_OK, 'filename.txt', 'text/plain');

		$this->assertEquals($stream->getSize(), $upload->getSize());
		$this->assertEquals('filename.txt', $upload->getClientFilename());
		$this->assertEquals('text/plain', $upload->getClientMediaType());

		$to = tempnam(sys_get_temp_dir(), 'successful');
		$this->cleanup[] = $to;
		$upload->moveTo($to);
		$this->assertFileExists($to);
		$this->assertEquals($stream->__toString(), file_get_contents($to));
	}

	public function testMoveCannotBeCalledMoreThanOnce(){
		$stream = $this->streamFactory->createStream('Foo bar!');
		$upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

		$this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'diac');
		$upload->moveTo($to);
		$this->assertTrue(file_exists($to));

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('moved');
		$upload->moveTo($to);
	}

	public function testCannotRetrieveStreamAfterMove(){
		$stream = $this->streamFactory->createStream('Foo bar!');
		$upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

		$this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'diac');
		$upload->moveTo($to);
		$this->assertFileExists($to);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('moved');
		$upload->getStream();
	}

	public function nonOkErrorStatus(){
		return [
			'UPLOAD_ERR_INI_SIZE'   => [UPLOAD_ERR_INI_SIZE],
			'UPLOAD_ERR_FORM_SIZE'  => [UPLOAD_ERR_FORM_SIZE],
			'UPLOAD_ERR_PARTIAL'    => [UPLOAD_ERR_PARTIAL],
			'UPLOAD_ERR_NO_FILE'    => [UPLOAD_ERR_NO_FILE],
			'UPLOAD_ERR_NO_TMP_DIR' => [UPLOAD_ERR_NO_TMP_DIR],
			'UPLOAD_ERR_CANT_WRITE' => [UPLOAD_ERR_CANT_WRITE],
			'UPLOAD_ERR_EXTENSION'  => [UPLOAD_ERR_EXTENSION],
		];
	}

	/**
	 * @dataProvider nonOkErrorStatus
	 *
	 * @param int $status
	 */
	public function testConstructorDoesNotRaiseExceptionForInvalidStreamWhenErrorStatusPresent(int $status){
		$uploadedFile = new UploadedFile('not ok', 0, $status);
		$this->assertSame($status, $uploadedFile->getError());
	}

	/**
	 * @dataProvider nonOkErrorStatus
	 *
	 * @param int $status
	 */
	public function testMoveToRaisesExceptionWhenErrorStatusPresent(int $status){
		$uploadedFile = new UploadedFile('not ok', 0, $status);
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('upload error');
		$uploadedFile->moveTo(__DIR__.'/'.uniqid());
	}

	/**
	 * @dataProvider nonOkErrorStatus
	 *
	 * @param int $status
	 */
	public function testGetStreamRaisesExceptionWhenErrorStatusPresent(int $status){
		$uploadedFile = new UploadedFile('not ok', 0, $status);
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('upload error');
		$uploadedFile->getStream();
	}

	public function testMoveToCreatesStreamIfOnlyAFilenameWasProvided(){
		$this->cleanup[] = $from = tempnam(sys_get_temp_dir(), 'copy_from');
		$this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'copy_to');

		copy(__FILE__, $from);

		$uploadedFile = new UploadedFile($from, 100, UPLOAD_ERR_OK, basename($from), 'text/plain');
		$uploadedFile->moveTo($to);

		$this->assertFileEquals(__FILE__, $to);
	}

	public function dataNormalizeFiles(){

		return [
			'Single file' => [
				[
					'file' => [
						'name' => 'MyFile.txt',
						'type' => 'text/plain',
						'tmp_name' => '/tmp/php/php1h4j1o',
						'error' => '0',
						'size' => '123'
					]
				],
				[
					'file' => new UploadedFile(
						'/tmp/php/php1h4j1o',
						123,
						UPLOAD_ERR_OK,
						'MyFile.txt',
						'text/plain'
					)
				]
			],
			'Empty file' => [
				[
					'image_file' => [
						'name' => '',
						'type' => '',
						'tmp_name' => '',
						'error' => '4',
						'size' => '0'
					]
				],
				[
					'image_file' => new UploadedFile(
						'',
						0,
						UPLOAD_ERR_NO_FILE,
						'',
						''
					)
				]
			],
			'Already Converted' => [
				[
					'file' => new UploadedFile(
						'/tmp/php/php1h4j1o',
						123,
						UPLOAD_ERR_OK,
						'MyFile.txt',
						'text/plain'
					)
				],
				[
					'file' => new UploadedFile(
						'/tmp/php/php1h4j1o',
						123,
						UPLOAD_ERR_OK,
						'MyFile.txt',
						'text/plain'
					)
				]
			],
			'Already Converted array' => [
				[
					'file' => [
						new UploadedFile(
							'/tmp/php/php1h4j1o',
							123,
							UPLOAD_ERR_OK,
							'MyFile.txt',
							'text/plain'
						),
						new UploadedFile(
							'',
							0,
							UPLOAD_ERR_NO_FILE,
							'',
							''
						)
					],
				],
				[
					'file' => [
						new UploadedFile(
							'/tmp/php/php1h4j1o',
							123,
							UPLOAD_ERR_OK,
							'MyFile.txt',
							'text/plain'
						),
						new UploadedFile(
							'',
							0,
							UPLOAD_ERR_NO_FILE,
							'',
							''
						)
					],
				]
			],
			'Multiple files' => [
				[
					'text_file' => [
						'name' => 'MyFile.txt',
						'type' => 'text/plain',
						'tmp_name' => '/tmp/php/php1h4j1o',
						'error' => '0',
						'size' => '123'
					],
					'image_file' => [
						'name' => '',
						'type' => '',
						'tmp_name' => '',
						'error' => '4',
						'size' => '0'
					]
				],
				[
					'text_file' => new UploadedFile(
						'/tmp/php/php1h4j1o',
						123,
						UPLOAD_ERR_OK,
						'MyFile.txt',
						'text/plain'
					),
					'image_file' => new UploadedFile(
						'',
						0,
						UPLOAD_ERR_NO_FILE,
						'',
						''
					)
				]
			],
			'Nested files' => [
				[
					'file' => [
						'name' => [
							0 => 'MyFile.txt',
							1 => 'Image.png',
						],
						'type' => [
							0 => 'text/plain',
							1 => 'image/png',
						],
						'tmp_name' => [
							0 => '/tmp/php/hp9hskjhf',
							1 => '/tmp/php/php1h4j1o',
						],
						'error' => [
							0 => '0',
							1 => '0',
						],
						'size' => [
							0 => '123',
							1 => '7349',
						],
					],
					'nested' => [
						'name' => [
							'other' => 'Flag.txt',
							'test' => [
								0 => 'Stuff.txt',
								1 => '',
							],
						],
						'type' => [
							'other' => 'text/plain',
							'test' => [
								0 => 'text/plain',
								1 => '',
							],
						],
						'tmp_name' => [
							'other' => '/tmp/php/hp9hskjhf',
							'test' => [
								0 => '/tmp/php/asifu2gp3',
								1 => '',
							],
						],
						'error' => [
							'other' => '0',
							'test' => [
								0 => '0',
								1 => '4',
							],
						],
						'size' => [
							'other' => '421',
							'test' => [
								0 => '32',
								1 => '0',
							]
						]
					],
				],
				[
					'file' => [
						0 => new UploadedFile(
							'/tmp/php/hp9hskjhf',
							123,
							UPLOAD_ERR_OK,
							'MyFile.txt',
							'text/plain'
						),
						1 => new UploadedFile(
							'/tmp/php/php1h4j1o',
							7349,
							UPLOAD_ERR_OK,
							'Image.png',
							'image/png'
						),
					],
					'nested' => [
						'other' => new UploadedFile(
							'/tmp/php/hp9hskjhf',
							421,
							UPLOAD_ERR_OK,
							'Flag.txt',
							'text/plain'
						),
						'test' => [
							0 => new UploadedFile(
								'/tmp/php/asifu2gp3',
								32,
								UPLOAD_ERR_OK,
								'Stuff.txt',
								'text/plain'
							),
							1 => new UploadedFile(
								'',
								0,
								UPLOAD_ERR_NO_FILE,
								'',
								''
							),
						]
					]
				]
			]
		];
	}

	/**
	 * @dataProvider dataNormalizeFiles
	 *
	 * @param array $files
	 * @param array $expected
	 */
	public function testNormalizeFiles(array $files, array $expected){
		$result = Psr7\normalize_files($files);

		$this->assertEquals($expected, $result);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Invalid value in files specification
	 */
	public function testNormalizeFilesRaisesException(){
		Psr7\normalize_files(['test' => 'something']);
	}

}
