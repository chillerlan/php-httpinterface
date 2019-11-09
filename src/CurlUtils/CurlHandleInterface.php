<?php
/**
 * Interface CurlHandleInterface
 *
 * @filesource   CurlHandleInterface.php
 * @created      13.08.2019
 * @package      chillerlan\HTTP\CurlUtils
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2019 smiley
 * @license      MIT
 */

namespace chillerlan\HTTP\CurlUtils;

use chillerlan\Settings\SettingsContainerInterface;
use Psr\Http\Message\{RequestInterface, ResponseInterface};

use const CURLE_COULDNT_CONNECT, CURLE_COULDNT_RESOLVE_HOST, CURLE_COULDNT_RESOLVE_PROXY,
	CURLE_GOT_NOTHING, CURLE_OPERATION_TIMEOUTED, CURLE_SSL_CONNECT_ERROR;

/**
 * @property resource                            $curl
 * @property int                                 $id
 * @property int                                 $retries
 * @property \Psr\Http\Message\RequestInterface  $request
 * @property \Psr\Http\Message\ResponseInterface $response
 */
interface CurlHandleInterface{

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
	 * CurlHandleInterface constructor.
	 *
	 * @param \Psr\Http\Message\RequestInterface              $request
	 * @param \Psr\Http\Message\ResponseInterface             $response
	 * @param \chillerlan\Settings\SettingsContainerInterface $options
	 */
	public function __construct(RequestInterface $request, ResponseInterface $response, SettingsContainerInterface $options);

	/**
	 * @return \chillerlan\HTTP\CurlUtils\CurlHandleInterface
	 */
	public function init():CurlHandleInterface;

	/**
	 * @return \chillerlan\HTTP\CurlUtils\CurlHandleInterface
	 */
	public function close():CurlHandleInterface;

}
