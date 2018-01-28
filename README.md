# chillerlan/php-httpinterface

A [http client wrapper](https://github.com/chillerlan/php-oauth/tree/afeb3efa7fb31710c7fd3d2909772e6177c8196a/src/HTTP) for PHP 7+.

[![version][packagist-badge]][packagist]
[![license][license-badge]][license]
[![Travis][travis-badge]][travis]
[![Coverage][coverage-badge]][coverage]
[![Scrunitizer][scrutinizer-badge]][scrutinizer]
[![Packagist downloads][downloads-badge]][downloads]
[![PayPal donate][donate-badge]][donate]

[packagist-badge]: https://img.shields.io/packagist/v/chillerlan/php-httpinterface.svg?style=flat-square
[packagist]: https://packagist.org/packages/chillerlan/php-httpinterface
[license-badge]: https://img.shields.io/github/license/chillerlan/php-httpinterface.svg?style=flat-square
[license]: https://github.com/chillerlan/php-httpinterface/blob/master/LICENSE
[travis-badge]: https://img.shields.io/travis/chillerlan/php-httpinterface.svg?style=flat-square
[travis]: https://travis-ci.org/chillerlan/php-httpinterface
[coverage-badge]: https://img.shields.io/codecov/c/github/chillerlan/php-httpinterface.svg?style=flat-square
[coverage]: https://codecov.io/github/chillerlan/php-httpinterface
[scrutinizer-badge]: https://img.shields.io/scrutinizer/g/chillerlan/php-httpinterface.svg?style=flat-square
[scrutinizer]: https://scrutinizer-ci.com/g/chillerlan/php-httpinterface
[downloads-badge]: https://img.shields.io/packagist/dt/chillerlan/php-httpinterface.svg?style=flat-square
[downloads]: https://packagist.org/packages/chillerlan/php-httpinterface/stats
[donate-badge]: https://img.shields.io/badge/donate-paypal-ff33aa.svg?style=flat-square
[donate]: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=WLYUNAT9ZTJZ4

### `HTTPClientInterface`
method | return 
------ | ------ 
`request(string $url, array $params = null, string $method = null, $body = null, array $headers = null)` | `HTTPResponseInterface` 
`normalizeRequestHeaders(array $headers)` | array
`buildQuery(array $params, bool $urlencode = null, string $delimiter = null, string $enclosure = null)` | string
`checkQueryParams(array $params, bool $booleans_as_string = null)` | array

### `HTTPClientTrait`
The `HTTPClientTrait` provides several (protected) shortcut methods for the `HTTPClientInterface`.

method | return 
------ | ------ 
`setHTTPClient(HTTPClientInterface $http)` | `$this`
`httpRequest(string $url, array $params = null, string $method = null, $body = null, array $headers = null)` | `HTTPResponseInterface`
`httpDELETE(string $url, array $params = null, array $headers = null)` | `HTTPResponseInterface`
`httpGET(string $url, array $params = null, array $headers = null)` | `HTTPResponseInterface`
`httpPATCH(string $url, array $params = null, $body = null, array $headers = null)` | `HTTPResponseInterface`
`httpPOST(string $url, array $params = null, $body = null, array $headers = null)` | `HTTPResponseInterface`
`httpPUT(string $url, array $params = null, $body = null, array $headers = null)` | `HTTPResponseInterface`
`normalizeRequestHeaders(array $headers)` | array
`checkQueryParams($params, bool $booleans_as_string = null)` | mixed
`httpBuildQuery(array $params, bool $urlencode = null, string $delimiter = null, string $enclosure = null)` | string
