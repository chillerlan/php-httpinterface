# chillerlan/php-httpinterface

A [PSR-7](https://www.php-fig.org/psr/psr-7/)/[PSR-17](https://www.php-fig.org/psr/psr-17/)/[PSR-18](https://www.php-fig.org/psr/psr-18/) implementation for PHP 7.2+.

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

# Documentation

## Requirements
- PHP 7.2+
  - the cURL extension if you plan to use the `CurlClient` class

## Installation
**requires [composer](https://getcomposer.org)**

*composer.json* (note: replace `dev-master` with a [version boundary](https://getcomposer.org/doc/articles/versions.md))
```json
{
	"require": {
		"php": "^7.2",
		"chillerlan/php-httpinterface": "dev-master"
	}
}
```

### Manual installation
Download the desired version of the package from [master](https://github.com/chillerlan/php-httpinterface/archive/master.zip) or
[release](https://github.com/chillerlan/php-httpinterface/releases) and extract the contents to your project folder.  After that:
- run `composer install` to install the required dependencies and generate `/vendor/autoload.php`.
- if you use a custom autoloader, point the namespace `chillerlan\HTTP` to the folder `src` of the package

Profit!

## Usage

### [`HTTPClientInterface`](https://github.com/chillerlan/php-httpinterface/blob/master/src/HTTPClientInterface.php)
A `HTTPClientInterface` is usually invoked with a [`HTTPOptions`](https://github.com/chillerlan/php-httpinterface/blob/master/src/HTTPOptions.php) object as the first (optional) parameter,
and - depending on the client - followed by one or more optional [PSR-17](https://www.php-fig.org/psr/psr-17/) message factories.
```php
$options = new HTTPOptions([
	'ca_info'    => '/path/to/cacert.pem',
	'user_agent' => 'my cool user agent 1.0',
]);

$http = new CurlClient($options, $myRequestFactory, $myResponseFactory);
```
You can now fire a request via the implemented [PSR-18](https://www.php-fig.org/psr/psr-18/) method `ClientInterface::sendRequest()`,
using an existing [PSR-7](https://www.php-fig.org/psr/psr-7/) `RequestInterface`...
```php
use chillerlan\HTTP\Psr7\Request;

$request = new Request('GET', 'https://www.example.com?foo=bar');

$http->sendRequest($request);
```
...or you can use the `HTTPClientInterface::request()` method, which creates a new request using the provided (if any) factories.
The `HTTPClientInterface` also provides constants for the HTTP methods via the [`RequestMethodInterface`](https://github.com/php-fig/http-message-util/blob/master/src/RequestMethodInterface.php).
```php
$http->request('https://www.example.com', $http::METHOD_GET, ['foo' => 'bar']);
```
Both methods will return a PSR-7 `ResponseInterface`.

### [PSR-7](https://www.php-fig.org/psr/psr-7/) Message helpers
These static methods can be found in the `chillerlan\HTTP\Psr7` namespace:

- `normalize_request_headers(array $headers)`
- `r_rawurlencode($data)` - recursive rawurlencode, accepts a string or an array as input
- `build_http_query(array $params, bool $urlencode = null, string $delimiter = null, string $enclosure = null)` - see [abraham/twitteroauth](https://github.com/abraham/twitteroauth/blob/master/src/Util.php#L82)
- `clean_query_params(iterable $params, int $bool_cast = null, bool $remove_empty = null)` - clean an array of parameters for URL queries (or JSON output etc.) using the following cast formats:
  - `BOOLEANS_AS_BOOL` - bool types will be left untouched (default)
  - `BOOLEANS_AS_INT` - cast to integer `1` and `0`
  - `BOOLEANS_AS_STRING` - a string value `"true"` and `"false"`
  - `BOOLEANS_AS_INT_STRING` - integer values, but as string,  `"1"` and `"0"`
- `merge_query(string $uri, array $query)` - merges an array of parameters into an URL query string
- `normalize_files(array $files)`
- `create_uploaded_file_from_spec(array $value)`
- `normalize_nested_file_spec(array $files = [])`
- `get_json(ResponseInterface $response, bool $assoc = null)`
- `get_xml(ResponseInterface $response)`
- `message_to_string(MessageInterface $message)` - returns the string representation of a `MessageInterface`

### [PSR-17](https://www.php-fig.org/psr/psr-17/) Factory helpers
These static methods can be found in the `chillerlan\HTTP\Psr17` namespace:

- `create_server_request_from_globals()` - creates a PSR-7 `ServerRequestInterface` object that is populated with the GPCS superglobals.
- `create_uri_from_globals()` - creates a PSR-7 `UriInterface` object that is populated with values from `$_SERVER`.
- `create_stream(string $content = '')` - creates a PSR-7 `StreamInterface` object from a string.
- `create_stream_from_input($in = null)` - creates a PSR-7 `StreamInterface` object from guessed input (string/scalar, file path, resource, object)
