<?php
/**
 * Class File
 *
 * @created      28.03.2021
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2021 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;
use function array_keys, is_array;

/**
 *
 */
class File{

	/**
	 * @link http://svn.apache.org/repos/asf/httpd/httpd/branches/1.3.x/conf/mime.types
	 */
	public const MIMETYPES = [
		'3gp'     => 'video/3gpp',
		'7z'      => 'application/x-7z-compressed',
		'aac'     => 'audio/x-aac',
		'ai'      => 'application/postscript',
		'aif'     => 'audio/x-aiff',
		'asc'     => 'text/plain',
		'asf'     => 'video/x-ms-asf',
		'atom'    => 'application/atom+xml',
		'avi'     => 'video/x-msvideo',
		'bmp'     => 'image/bmp',
		'bz2'     => 'application/x-bzip2',
		'cer'     => 'application/pkix-cert',
		'crl'     => 'application/pkix-crl',
		'crt'     => 'application/x-x509-ca-cert',
		'css'     => 'text/css',
		'csv'     => 'text/csv',
		'cu'      => 'application/cu-seeme',
		'deb'     => 'application/x-debian-package',
		'doc'     => 'application/msword',
		'docx'    => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'dvi'     => 'application/x-dvi',
		'eot'     => 'application/vnd.ms-fontobject',
		'eps'     => 'application/postscript',
		'epub'    => 'application/epub+zip',
		'etx'     => 'text/x-setext',
		'flac'    => 'audio/flac',
		'flv'     => 'video/x-flv',
		'gif'     => 'image/gif',
		'gz'      => 'application/gzip',
		'htm'     => 'text/html',
		'html'    => 'text/html',
		'ico'     => 'image/x-icon',
		'ics'     => 'text/calendar',
		'ini'     => 'text/plain',
		'iso'     => 'application/x-iso9660-image',
		'jar'     => 'application/java-archive',
		'jpe'     => 'image/jpeg',
		'jpeg'    => 'image/jpeg',
		'jpg'     => 'image/jpeg',
		'js'      => 'text/javascript',
		'json'    => 'application/json',
		'latex'   => 'application/x-latex',
		'log'     => 'text/plain',
		'm4a'     => 'audio/mp4',
		'm4v'     => 'video/mp4',
		'mid'     => 'audio/midi',
		'midi'    => 'audio/midi',
		'mov'     => 'video/quicktime',
		'mkv'     => 'video/x-matroska',
		'mp3'     => 'audio/mpeg',
		'mp4'     => 'video/mp4',
		'mp4a'    => 'audio/mp4',
		'mp4v'    => 'video/mp4',
		'mpe'     => 'video/mpeg',
		'mpeg'    => 'video/mpeg',
		'mpg'     => 'video/mpeg',
		'mpg4'    => 'video/mp4',
		'oga'     => 'audio/ogg',
		'ogg'     => 'audio/ogg',
		'ogv'     => 'video/ogg',
		'ogx'     => 'application/ogg',
		'pbm'     => 'image/x-portable-bitmap',
		'pdf'     => 'application/pdf',
		'pgm'     => 'image/x-portable-graymap',
		'png'     => 'image/png',
		'pnm'     => 'image/x-portable-anymap',
		'ppm'     => 'image/x-portable-pixmap',
		'ppt'     => 'application/vnd.ms-powerpoint',
		'pptx'    => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'ps'      => 'application/postscript',
		'qt'      => 'video/quicktime',
		'rar'     => 'application/x-rar-compressed',
		'ras'     => 'image/x-cmu-raster',
		'rss'     => 'application/rss+xml',
		'rtf'     => 'application/rtf',
		'sgm'     => 'text/sgml',
		'sgml'    => 'text/sgml',
		'svg'     => 'image/svg+xml',
		'swf'     => 'application/x-shockwave-flash',
		'tar'     => 'application/x-tar',
		'tif'     => 'image/tiff',
		'tiff'    => 'image/tiff',
		'torrent' => 'application/x-bittorrent',
		'ttf'     => 'application/x-font-ttf',
		'txt'     => 'text/plain',
		'wav'     => 'audio/x-wav',
		'webm'    => 'video/webm',
		'wma'     => 'audio/x-ms-wma',
		'wmv'     => 'video/x-ms-wmv',
		'woff'    => 'application/x-font-woff',
		'wsdl'    => 'application/wsdl+xml',
		'xbm'     => 'image/x-xbitmap',
		'xls'     => 'application/vnd.ms-excel',
		'xlsx'    => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'xml'     => 'application/xml',
		'xpm'     => 'image/x-xpixmap',
		'xwd'     => 'image/x-xwindowdump',
		'yaml'    => 'text/yaml',
		'yml'     => 'text/yaml',
		'zip'     => 'application/zip',
	];

	/**
	 * Return an UploadedFile instance array.
	 *
	 * @param array $files A array which respect $_FILES structure
	 *
	 * @return array
	 * @throws \InvalidArgumentException for unrecognized values
	 */
	public static function normalize(array $files):array{
		$normalized = [];

		foreach($files as $key => $value){

			if($value instanceof UploadedFileInterface){
				$normalized[$key] = $value;
			}
			elseif(is_array($value) && isset($value['tmp_name'])){
				$normalized[$key] = self::createUploadedFileFromSpec($value);
			}
			elseif(is_array($value)){
				$normalized[$key] = self::normalize($value);
			}
			else{
				throw new InvalidArgumentException('Invalid value in files specification');
			}

		}

		return $normalized;
	}

	/**
	 * Create and return an UploadedFile instance from a $_FILES specification.
	 *
	 * If the specification represents an array of values, this method will
	 * delegate to normalizeNestedFileSpec() and return that return value.
	 *
	 * @param array $value $_FILES struct
	 *
	 * @return array|\Psr\Http\Message\UploadedFileInterface
	 */
	public static function createUploadedFileFromSpec(array $value){

		if(is_array($value['tmp_name'])){
			return self::normalizeNestedFileSpec($value);
		}

		return new UploadedFile($value['tmp_name'], (int)$value['size'], (int)$value['error'], $value['name'], $value['type']);
	}

	/**
	 * Normalize an array of file specifications.
	 *
	 * Loops through all nested files and returns a normalized array of
	 * UploadedFileInterface instances.
	 *
	 * @param array $files
	 *
	 * @return \Psr\Http\Message\UploadedFileInterface[]
	 */
	public static function normalizeNestedFileSpec(array $files):array{
		$normalized = [];

		foreach(array_keys($files['tmp_name']) as $key){
			$spec = [
				'tmp_name' => $files['tmp_name'][$key],
				'size'     => $files['size'][$key],
				'error'    => $files['error'][$key],
				'name'     => $files['name'][$key],
				'type'     => $files['type'][$key],
			];

			$normalized[$key] = self::createUploadedFileFromSpec($spec);
		}

		return $normalized;
	}

}
