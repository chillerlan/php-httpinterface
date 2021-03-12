# chillerlan/php-httpinterface

A [PSR-7](https://www.php-fig.org/psr/psr-7/)/[PSR-17](https://www.php-fig.org/psr/psr-17/)/[PSR-18](https://www.php-fig.org/psr/psr-18/) implementation for PHP 7.4+.

[![PHP Version Support][php-badge]][php]
[![version][packagist-badge]][packagist]
[![license][license-badge]][license]
[![Travis][travis-badge]][travis]
[![Coverage][coverage-badge]][coverage]
[![Scrunitizer][scrutinizer-badge]][scrutinizer]
[![Packagist downloads][downloads-badge]][downloads]<br/>
[![Continuous Integration][gh-action-badge]][gh-action]

[php-badge]: https://img.shields.io/packagist/php-v/chillerlan/php-httpinterface?logo=php&color=8892BF
[php]: https://www.php.net/supported-versions.php
[packagist-badge]: https://img.shields.io/packagist/v/chillerlan/php-httpinterface.svg
[packagist]: https://packagist.org/packages/chillerlan/php-httpinterface
[license-badge]: https://img.shields.io/github/license/chillerlan/php-httpinterface.svg
[license]: https://github.com/chillerlan/php-httpinterface/blob/master/LICENSE
[travis-badge]: https://img.shields.io/travis/com/chillerlan/php-httpinterface/main.svg?logo=travis
[travis]: https://travis-ci.com/github/chillerlan/php-httpinterface
[coverage-badge]: https://img.shields.io/codecov/c/github/chillerlan/php-httpinterface.svg?logo=codecov
[coverage]: https://codecov.io/github/chillerlan/php-httpinterface
[scrutinizer-badge]: https://img.shields.io/scrutinizer/g/chillerlan/php-httpinterface.svg?logo=scrutinizer
[scrutinizer]: https://scrutinizer-ci.com/g/chillerlan/php-httpinterface
[downloads-badge]: https://img.shields.io/packagist/dt/chillerlan/php-httpinterface.svg
[downloads]: https://packagist.org/packages/chillerlan/php-httpinterface/stats
[gh-action-badge]: https://github.com/chillerlan/php-httpinterface/workflows/Continuous%20Integration/badge.svg
[gh-action]: https://github.com/chillerlan/php-httpinterface/actions

# Documentation

## Requirements
- PHP 7.4+
  - the cURL extension

## Installation
**requires [composer](https://getcomposer.org)**

*composer.json* (note: replace `dev-master` with a [version boundary](https://getcomposer.org/doc/articles/versions.md))
```json
{
	"require": {
		"php": "^7.4",
		"chillerlan/php-httpinterface": "dev-master"
	}
}
```

Profit!

## API

### [PSR-7](https://www.php-fig.org/psr/psr-7/) Message interfaces & helpers
PSR-7 interface | class/signature
----------------|----------------
`RequestInterface` | `Request(string $method, $uri, array $headers = null, $body = null, string $version = null)`
`ServerRequestInterface` | `ServerRequest(string $method, $uri, array $headers = null, $body = null, string $version = null, array $serverParams = null)`
`ResponseInterface` | `Response(int $status = null, array $headers = null, $body = null, string $version = null, string $reason = null)`
`StreamInterface` | `Stream($stream)`
`StreamInterface` | `MultipartStream(array $elements = null, string $boundary = null)`
`UploadedFileInterface` | `UploadedFile($file, int $size, int $error = UPLOAD_ERR_OK, string $filename = null, string $mediaType = null)`
`UriInterface` | `Uri(string $uri = null)`

These static helper functions can be found in the `chillerlan\HTTP\Psr7` namespace:

function | description
---------|------------
`normalize_request_headers(array $headers)` | 
`r_rawurlencode($data)` |  recursive rawurlencode, accepts a string or an array as input
`build_http_query(array $params, bool $urlencode = null, string $delimiter = null, string $enclosure = null)` |  see [abraham/twitteroauth](https://github.com/abraham/twitteroauth/blob/master/src/Util.php#L82)
`clean_query_params(iterable $params, int $bool_cast = null, bool $remove_empty = null)` | clean an array of parameters for URL queries (or JSON output etc.) using the following cast formats:<br>`BOOLEANS_AS_BOOL` - bool types will be left untouched (default)<br>`BOOLEANS_AS_INT` - cast to integer `1` and `0`<br>`BOOLEANS_AS_STRING` - a string value `"true"` and `"false"`<br>`BOOLEANS_AS_INT_STRING` - integer values, but as string,  `"1"` and `"0"`
`merge_query(string $uri, array $query)` | merges an array of query parameters into an URL query string
`normalize_files(array $files)` | 
`create_uploaded_file_from_spec(array $value)` | 
`normalize_nested_file_spec(array $files = [])` | 
`get_json(ResponseInterface $response, bool $assoc = null)` | 
`get_xml(ResponseInterface $response)` | 
`message_to_string(MessageInterface $message)` | returns the string representation of a `MessageInterface`
`decompress_content(MessageInterface $message)` | decompresses the message content according to the `Content-Encoding` header and returns the decompressed data

### [PSR-15](https://www.php-fig.org/psr/psr-15/) Request handlers and middleware
These classes can be found in the `chillerlan\HTTP\Psr15` namespace:

PSR-15 interface | class/signature 
-----------------|----------------
`RequestHandlerInterface` | `EmptyResponseHandler(ResponseFactoryInterface $responseFactory, int $status)` 
`RequestHandlerInterface` | `QueueRunner(array $middlewareStack, RequestHandlerInterface $fallbackHandler)`
`RequestHandlerInterface`, `MiddlewareInterface` | `QueueDispatcher(iterable $middlewareStack = null, RequestHandlerInterface $fallbackHandler = null)` 
`RequestHandlerInterface`, `MiddlewareInterface` | `PriorityQueueDispatcher(iterable $middlewareStack = null, RequestHandlerInterface $fallbackHandler = null)`
`MiddlewareInterface` | `PriorityMiddleware(MiddlewareInterface $middleware, int $priority = null)`

#### QueueDispatcher example

```php
// an iterable that contains several PSR-15 MiddlewareInterfaces
$middlewareStack = [
    // ...
];

// Fallback handler, using a PSR-17 ResponseFactory:
$fallbackHandler = new EmptyResponseHandler($responseFactoryInterface, 200);

// Create request handler instance:
$handler = new QueueDispatcher($middlewareStack, $fallbackHandler);

// manually add a middleware
$handler->add($middlewareInterface);

// execute it:
$response = $handler->handle($serverRequestInterface);
```
The `PriorityQueueDispatcher` works similar, with the difference that it also accepts `PriorityMiddlewareInterface` in the middleware stack, which allows you to specify a priority to control the order of execution.

### [PSR-17](https://www.php-fig.org/psr/psr-17/) Factories & helpers
PSR-17 interface | class/signature
-----------------|----------------
`RequestFactoryInterface` | `RequestFactory()`
`ResponseFactoryInterface` | `ResponseFactory()`
`ServerRequestFactoryInterface` | `ServerRequestFactory()`
`StreamFactoryInterface` | `StreamFactory()`
`UploadedFileFactoryInterface` | `UploadedFileFactory()`
`UriFactoryInterface` | `UriFactory()`

These static functions can be found in the `chillerlan\HTTP\Psr17` namespace:

function | description
---------|------------
`create_server_request_from_globals()` | creates a PSR-7 `ServerRequestInterface` object that is populated with the GPCS superglobals
`create_uri_from_globals()` | creates a PSR-7 `UriInterface` object that is populated with values from `$_SERVER`
`create_stream(string $content = '')` | creates a PSR-7 `StreamInterface` object from a string
`create_stream_from_input($in = null)` | creates a PSR-7 `StreamInterface` object from guessed input (string/scalar, resource, object)

### [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP Clients
These classes can be found in the `chillerlan\HTTP\Psr18` namespace:

class/signature | description
----------------|------------
`CurlClient` | a native cURL client
`StreamClient` | a client that uses PHP's stream methods (still requires cURL)
`URLExtractor` | a client that resolves shortened links (such as `t.co` or `goo.gl`) and returns the response for the last (deepest) URL
`LoggingClient` | a logger client that wraps another `ClientInterface` and utilizes a `LoggerInterface` to log the request and response objects

The namespace `chillerlan\HTTP\CurlUtils` contains several classes related to `CurlClient`

class | description
------|------------
`CurlHandle` | used in `CurlClient` and `CurlMultiClient` 
`CurlMultiClient` | a `curl_multi` / "[Rolling Curl](http://www.onlineaspect.com/2009/01/26/how-to-use-curl_multi-without-blocking/)" implementation
`MultiResponseHandlerInterface` | the response handler for `CurlMultiClient`

#### HTTP client example
The built-in HTTP clients are usually invoked with a [`HTTPOptions`](https://github.com/chillerlan/php-httpinterface/blob/master/src/HTTPOptions.php) object as the first (optional) parameter,
and - depending on the client - followed by one or more optional [PSR-17](https://www.php-fig.org/psr/psr-17/) message factories and a 
PSR-3 `LoggerInterface`.
```php
$options = new HTTPOptions([
	'ca_info'    => '/path/to/cacert.pem',
	'user_agent' => 'my cool user agent 1.0',
]);

$http = new CurlClient($options, $myResponseFactory);
```
You can now fire a request via the implemented PSR-18 method `ClientInterface::sendRequest()`,
using an existing PSR-7 `RequestInterface` and expect a PSR-7 `ResponseInterface`.
```php
use chillerlan\HTTP\Psr7\Request;

$request = new Request('GET', 'https://www.example.com?foo=bar');

$http->sendRequest($request);
```
