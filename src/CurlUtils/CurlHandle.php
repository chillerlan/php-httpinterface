<?php
/**
 * Class CurlHandle
 *
 * @created      30.08.2018
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2018 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\CurlUtils;

use chillerlan\Settings\SettingsContainerInterface;
use Psr\Http\Message\{RequestInterface, ResponseInterface};

use function array_key_exists, count, curl_close, curl_init, curl_setopt_array,
	explode, in_array, is_resource, strlen, strtolower, strtoupper, substr, trim;

use const CURLOPT_CAINFO, CURLOPT_CONNECTTIMEOUT, CURLOPT_CUSTOMREQUEST, CURLOPT_FOLLOWLOCATION, CURLOPT_HEADER,
	CURLOPT_HEADERFUNCTION, CURLOPT_HTTP_VERSION, CURLOPT_HTTPHEADER, CURLOPT_INFILESIZE, CURLOPT_MAXREDIRS,
	CURLOPT_NOBODY, CURLOPT_POSTFIELDS, CURLOPT_PROTOCOLS, CURLOPT_READFUNCTION, CURLOPT_RETURNTRANSFER,
	CURLOPT_SSL_VERIFYHOST, CURLOPT_SSL_VERIFYPEER, CURLOPT_SSL_VERIFYSTATUS, CURLOPT_TIMEOUT, CURLOPT_UPLOAD, CURLOPT_URL,
	CURLOPT_USERAGENT, CURLOPT_USERPWD, CURLOPT_WRITEFUNCTION, CURLPROTO_HTTP, CURLPROTO_HTTPS, CURL_HTTP_VERSION_2TLS,
	CURLE_COULDNT_CONNECT, CURLE_COULDNT_RESOLVE_HOST, CURLE_COULDNT_RESOLVE_PROXY,
	CURLE_GOT_NOTHING, CURLE_OPERATION_TIMEOUTED, CURLE_SSL_CONNECT_ERROR;

class CurlHandle{

	const CURL_NETWORK_ERRORS = [
		CURLE_COULDNT_RESOLVE_PROXY,
		CURLE_COULDNT_RESOLVE_HOST,
		CURLE_COULDNT_CONNECT,
		CURLE_OPERATION_TIMEOUTED,
		CURLE_SSL_CONNECT_ERROR,
		CURLE_GOT_NOTHING,
	];

	// https://www.php.net/manual/function.curl-getinfo.php#111678
	// https://www.openssl.org/docs/manmaster/man1/verify.html#VERIFY_OPERATION
	// https://github.com/openssl/openssl/blob/91cb81d40a8102c3d8667629661be8d6937db82b/include/openssl/x509_vfy.h#L99-L189
	const CURLINFO_SSL_VERIFYRESULT = [
		0  => 'ok the operation was successful.',
		2  => 'unable to get issuer certificate',
		3  => 'unable to get certificate CRL',
		4  => 'unable to decrypt certificate\'s signature',
		5  => 'unable to decrypt CRL\'s signature',
		6  => 'unable to decode issuer public key',
		7  => 'certificate signature failure',
		8  => 'CRL signature failure',
		9  => 'certificate is not yet valid',
		10 => 'certificate has expired',
		11 => 'CRL is not yet valid',
		12 => 'CRL has expired',
		13 => 'format error in certificate\'s notBefore field',
		14 => 'format error in certificate\'s notAfter field',
		15 => 'format error in CRL\'s lastUpdate field',
		16 => 'format error in CRL\'s nextUpdate field',
		17 => 'out of memory',
		18 => 'self signed certificate',
		19 => 'self signed certificate in certificate chain',
		20 => 'unable to get local issuer certificate',
		21 => 'unable to verify the first certificate',
		22 => 'certificate chain too long',
		23 => 'certificate revoked',
		24 => 'invalid CA certificate',
		25 => 'path length constraint exceeded',
		26 => 'unsupported certificate purpose',
		27 => 'certificate not trusted',
		28 => 'certificate rejected',
		29 => 'subject issuer mismatch',
		30 => 'authority and subject key identifier mismatch',
		31 => 'authority and issuer serial number mismatch',
		32 => 'key usage does not include certificate signing',
		50 => 'application verification failure',
	];

	/**
	 * The cURL handle
	 * @phan-suppress PhanUndeclaredTypeProperty
	 * @var resource|\CurlHandle|null
	 */
	protected $curl;

	/**
	 * @var \chillerlan\Settings\SettingsContainerInterface|\chillerlan\HTTP\HTTPOptions
	 */
	protected SettingsContainerInterface $options;

	protected RequestInterface $request;

	protected ResponseInterface $response;

	/**
	 * CurlHandle constructor.
	 */
	public function __construct(RequestInterface $request, ResponseInterface $response, SettingsContainerInterface $options){
		$this->request  = $request;
		$this->response = $response;
		$this->options  = $options;
		$this->curl     = curl_init();
	}

	/**
	 * close an existing cURL handle on exit
	 */
	public function __destruct(){
		$this->close();
	}

	/**
	 * @return \chillerlan\HTTP\CurlUtils\CurlHandle
	 */
	public function close():CurlHandle{

		if(is_resource($this->curl)){
			curl_close($this->curl);
		}

		return $this;
	}

	/**
	 * @phan-suppress PhanUndeclaredTypeReturnType
	 * @return resource|\CurlHandle|null
	 * @codeCoverageIgnore
	 */
	public function getCurlResource(){
		return $this->curl;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getRequest():RequestInterface{
		return $this->request;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function getResponse():ResponseInterface{
		return $this->response;
	}

	/**
	 * @link https://php.watch/articles/php-curl-security-hardening
	 *
	 * @return array
	 */
	protected function initCurlOptions():array{
		return [
			CURLOPT_HEADER           => false,
			CURLOPT_RETURNTRANSFER   => true,
			CURLOPT_FOLLOWLOCATION   => false,
			CURLOPT_MAXREDIRS        => 5,
			CURLOPT_URL              => (string)$this->request->getUri()->withFragment(''),
			CURLOPT_HTTP_VERSION     => CURL_HTTP_VERSION_2TLS,
			CURLOPT_USERAGENT        => $this->options->user_agent,
			CURLOPT_PROTOCOLS        => CURLPROTO_HTTP | CURLPROTO_HTTPS,
			CURLOPT_REDIR_PROTOCOLS  => CURLPROTO_HTTPS,
			CURLOPT_SSL_VERIFYPEER   => true,
			CURLOPT_SSL_VERIFYHOST   => 2,
			CURLOPT_SSL_VERIFYSTATUS => $this->options->curl_check_OCSP,
			CURLOPT_CAINFO           => $this->options->ca_info,
			CURLOPT_TIMEOUT          => $this->options->timeout,
			CURLOPT_CONNECTTIMEOUT   => 30,
			CURLOPT_WRITEFUNCTION    => [$this, 'writefunction'],
			CURLOPT_HEADERFUNCTION   => [$this, 'headerfunction'],
		];
	}

	/**
	 * @param array $options
	 *
	 * @return array
	 */
	protected function setBodyOptions(array $options):array{
		$body     = $this->request->getBody();
		$bodySize = $body->getSize();

		if($bodySize === 0){
			return $options;
		}

		if($body->isSeekable()){
			$body->rewind();
		}

		// Message has non empty body.
		if($bodySize === null || $bodySize > 1 << 20){
			// Avoid full loading large or unknown size body into memory
			$options[CURLOPT_UPLOAD] = true;

			if($bodySize !== null){
				$options[CURLOPT_INFILESIZE] = $bodySize;
			}

			$options[CURLOPT_READFUNCTION] = [$this, 'readfunction'];
		}
		// Small body can be loaded into memory
		else{
			$options[CURLOPT_POSTFIELDS] = (string)$body;
		}

		return $options;
	}

	/**
	 * @param array $options
	 *
	 * @return array
	 */
	protected function setHeaderOptions(array $options):array{
		$headers = [];

		foreach($this->request->getHeaders() as $name => $values){
			$header = strtolower($name);

			// curl-client does not support "Expect-Continue", so dropping "expect" headers
			if($header === 'expect'){
				continue;
			}

			if($header === 'content-length'){

				// Small body content length can be calculated here.
				if(array_key_exists(CURLOPT_POSTFIELDS, $options)){
					$values = [strlen($options[CURLOPT_POSTFIELDS])];
				}
				// Else if there is no body, forcing "Content-length" to 0
				elseif(!array_key_exists(CURLOPT_READFUNCTION, $options)){
					$values = ['0'];
				}

			}

			foreach($values as $value){
				$value = (string)$value;

				// cURL requires a special format for empty headers.
				// See https://github.com/guzzle/guzzle/issues/1882 for more details.
				$headers[] = $value === '' ? $name.';' : $name.': '.$value;
			}

		}

		// If the Expect header is not present (it isn't), prevent curl from adding it
		$headers[] = 'Expect:';

		// cURL sometimes adds a content-type by default. Prevent this.
		if(!$this->request->hasHeader('Content-Type')){
			$headers[] = 'Content-Type:';
		}

		$options[CURLOPT_HTTPHEADER] = $headers;

		return $options;
	}

	/**
	 * @return \chillerlan\HTTP\CurlUtils\CurlHandle
	 */
	public function init():CurlHandle{
		$options  = $this->initCurlOptions();
		$userinfo = $this->request->getUri()->getUserInfo();
		$method   = $this->request->getMethod();

		if(!empty($userinfo)){
			$options[CURLOPT_USERPWD] = $userinfo;
		}

		/*
		 * Some HTTP methods cannot have payload:
		 *
		 * - GET   — cURL will automatically change the method to PUT or POST
		 *           if we set CURLOPT_UPLOAD or CURLOPT_POSTFIELDS.
		 * - HEAD  — cURL treats HEAD as a GET request with same restrictions.
		 * - TRACE — According to RFC7231: a client MUST NOT send a message body in a TRACE request.
		 */

		if(in_array($method, ['DELETE', 'PATCH', 'POST', 'PUT'], true)){
			$options = $this->setBodyOptions($options);
		}

		// This will set HTTP method to "HEAD".
		if($method === 'HEAD'){
			$options[CURLOPT_NOBODY] = true;
		}

		// GET is a default method. Other methods should be specified explicitly.
		if($method !== 'GET'){
			$options[CURLOPT_CUSTOMREQUEST] = $method;
		}

		// overwrite the default values with $curl_options
		foreach($this->options->curl_options ?? [] as $k => $v){
			// skip some options that are only set automatically
			if(in_array($k, [CURLOPT_HTTPHEADER, CURLOPT_CUSTOMREQUEST, CURLOPT_NOBODY], true)){
				continue;
			}

			$options[$k] = $v;
		}

		$options = $this->setHeaderOptions($options);

		curl_setopt_array($this->curl, $options);

		return $this;
	}

	/**
	 * @internal
	 *
	 * @param resource $curl
	 * @param resource $stream
	 * @param int      $length
	 *
	 * @return string
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function readfunction($curl, $stream, int $length):string{
		return $this->request->getBody()->read($length);
	}

	/**
	 * @internal
	 *
	 * @param resource $curl
	 * @param string   $data
	 *
	 * @return int
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function writefunction($curl, string $data):int{
		return $this->response->getBody()->write($data);
	}

	/**
	 * @internal
	 *
	 * @param resource $curl
	 * @param string   $line
	 *
	 * @return int
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function headerfunction($curl, string $line):int{
		$str    = trim($line);
		$header = explode(':', $str, 2);

		if(count($header) === 2){
			$this->response = $this->response
				->withAddedHeader(trim($header[0]), trim($header[1]));
		}
		elseif(substr(strtoupper($str), 0, 5) === 'HTTP/'){
			$status = explode(' ', $str, 3);
			$reason = count($status) > 2 ? trim($status[2]) : '';

			$this->response = $this->response
				->withStatus((int)$status[1], $reason)
				->withProtocolVersion(substr($status[0], 5))
			;
		}

		return strlen($line);
	}

}
