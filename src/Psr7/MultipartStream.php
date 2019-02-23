<?php
/**
 * Class MultipartStream
 *
 * @link https://github.com/guzzle/psr7/blob/master/src/MultipartStream.php
 *
 * @filesource   MultipartStream.php
 * @created      20.12.2018
 * @package      chillerlan\HTTP\Psr7
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use chillerlan\HTTP\Psr17;
use InvalidArgumentException, RuntimeException;

/**
 * @property \chillerlan\HTTP\Psr7\Stream $stream
 */
final class MultipartStream extends StreamAbstract{

	/**
	 * @var string
	 */
	protected $boundary;

	/**
	 * @var bool
	 */
	protected $built = false;

	/**
	 * MultipartStream constructor.
	 *
	 * @param array        $elements [
	 *                                   name     => string,
	 *                                   contents => StreamInterface/resource/string,
	 *                                   headers  => array,
	 *                                   filename => string
	 *                               ]
	 * @param string|null  $boundary
	 */
	public function __construct(array $elements = null, string $boundary = null){
		$this->boundary = $boundary ?? sha1(random_bytes(1024));
		$this->stream   = Psr17\create_stream();

		foreach($elements ?? [] as $element){
			$this->addElement($element);
		}

	}

	/**
	 * @return string
	 */
	public function getBoundary():string{
		return $this->boundary;
	}

	/**
	 * @return \chillerlan\HTTP\Psr7\MultipartStream
	 */
	public function build():MultipartStream{

		if(!$this->built){
			$this->stream->write("--{$this->getBoundary()}--\r\n");

			$this->built = true;
		}

		$this->stream->rewind();

		return $this;
	}

	/**
	 * @param array $e
	 *
	 * @return \chillerlan\HTTP\Psr7\MultipartStream
	 */
	public function addElement(array $e):MultipartStream{

		if($this->built){
			throw new RuntimeException('Stream already built');
		}

		$e = array_merge(['filename' => null, 'headers' => []], $e);

		foreach(['contents', 'name'] as $key){
			if(!isset($e[$key])){
				throw new InvalidArgumentException('A "'.$key.'" element is required');
			}
		}

		$e['contents'] = Psr17\create_stream_from_input($e['contents']);

		if(empty($e['filename'])){
			$uri = $e['contents']->getMetadata('uri');

			if(substr($uri, 0, 6) !== 'php://'){
				$e['filename'] = $uri;
			}
		}

		$hasFilename = $e['filename'] === '0' || $e['filename'];

		// Set a default content-disposition header if none was provided
		if(!$this->hasHeader($e['headers'], 'content-disposition')){
			$e['headers']['Content-Disposition'] = 'form-data; name="'.$e['name'].'"'.($hasFilename ? '; filename="'.basename($e['filename']).'"' : '');
		}

		// Set a default content-length header if none was provided
		if(!$this->hasHeader($e['headers'], 'content-length')){
			$length = $e['contents']->getSize();

			if($length){
				$e['headers']['Content-Length'] = $length;
			}
		}

		// Set a default Content-Type if none was supplied
		if(!$this->hasHeader($e['headers'], 'content-type') && $hasFilename){
			$type = MIMETYPES[pathinfo($e['filename'], PATHINFO_EXTENSION)] ?? null;

			if($type){
				$e['headers']['Content-Type'] = $type;
			}
		}

		$this->stream->write('--'.$this->boundary."\r\n");

		foreach($e['headers'] as $key => $value){
			$this->stream->write($key.': '.$value."\r\n");
		}

		$this->stream->write("\r\n".$e['contents']->getContents()."\r\n");

		return $this;
	}

	/**
	 * @param array  $headers
	 * @param string $key
	 *
	 * @return bool
	 */
	private function hasHeader(array $headers, string $key):bool{
		$lowercaseHeader = strtolower($key);

		foreach ($headers as $k => $v) {
			if (strtolower($k) === $lowercaseHeader) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function __toString(){
		return $this->getContents();
	}

	/**
	 * @inheritdoc
	 */
	public function getSize():?int{
		return $this->stream->getSize() + strlen($this->boundary) + 6;
	}

	/**
	 * @inheritdoc
	 */
	public function isWritable():bool{
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function write($string):int{
		throw new RuntimeException('Cannot write to a MultipartStream, use MultipartStream::addElement() instead.');
	}

	/**
	 * @inheritdoc
	 */
	public function isReadable():bool{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function getContents():string{
		return $this->build()->stream->getContents();
	}

}