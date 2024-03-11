# chillerlan/php-httpinterface

A [PSR-7](https://www.php-fig.org/psr/psr-7/)/[PSR-17](https://www.php-fig.org/psr/psr-17/)/[PSR-18](https://www.php-fig.org/psr/psr-18/) implementation.

[![PHP Version Support][php-badge]][php]
[![version][packagist-badge]][packagist]
[![license][license-badge]][license]
[![Continuous Integration][gh-action-badge]][gh-action]
[![Coverage][coverage-badge]][coverage]
[![Codacy][codacy-badge]][codacy]
[![Packagist downloads][downloads-badge]][downloads]

[php-badge]: https://img.shields.io/packagist/php-v/chillerlan/php-httpinterface?logo=php&color=8892BF
[php]: https://www.php.net/supported-versions.php
[packagist-badge]: https://img.shields.io/packagist/v/chillerlan/php-httpinterface.svg?logo=packagist
[packagist]: https://packagist.org/packages/chillerlan/php-httpinterface
[license-badge]: https://img.shields.io/github/license/chillerlan/php-httpinterface.svg
[license]: https://github.com/chillerlan/php-httpinterface/blob/main/LICENSE
[gh-action-badge]: https://img.shields.io/github/actions/workflow/status/chillerlan/php-httpinterface/ci.yml?branch=main&logo=github
[gh-action]: https://github.com/chillerlan/php-httpinterface/actions/workflows/ci.yml?query=branch%3Amain
[coverage-badge]: https://img.shields.io/codecov/c/github/chillerlan/php-httpinterface.svg?logo=codecov
[coverage]: https://codecov.io/github/chillerlan/php-httpinterface
[codacy-badge]: https://img.shields.io/codacy/grade/0ad3a5f9abe547cca5d5b3dff0ba3383?logo=codacy
[codacy]: https://app.codacy.com/gh/chillerlan/php-httpinterface/dashboard
[downloads-badge]: https://img.shields.io/packagist/dt/chillerlan/php-httpinterface.svg?logo=packagist
[downloads]: https://packagist.org/packages/chillerlan/php-httpinterface/stats


# Documentation

An API documentation created with [phpDocumentor](https://www.phpdoc.org/) can be found at https://chillerlan.github.io/php-httpinterface/ (WIP).


## Requirements
- PHP 8.1+
  - [`ext-curl`](https://www.php.net/manual/book.curl.php)
  - [`ext-json`](https://www.php.net/manual/book.json.php)
  - [`ext-mbstring`](https://www.php.net/manual/book.mbstring.php)
  - [`ext-simplexml`](https://www.php.net/manual/book.simplexml.php)
  - [`ext-zlib`](https://www.php.net/manual/book.zlib.php)


## Installation
**requires [composer](https://getcomposer.org)**

*composer.json* (note: replace `dev-main` with a [version boundary](https://getcomposer.org/doc/articles/versions.md))
```json
{
	"require": {
		"php": "^8.1",
		"chillerlan/php-httpinterface": "dev-main"
	}
}
```
Note: replace `dev-main` with a [version constraint](https://getcomposer.org/doc/articles/versions.md#writing-version-constraints), e.g. `^5.0` - see [releases](https://github.com/chillerlan/php-httpinterface/releases) for valid versions.
In case you want to keep using `dev-main`, specify the hash of a commit to avoid running into unforseen issues like so: `dev-main#8ac7f056ef2d492b0c961da29472c27324218b83`

Profit!


## Quickstart

The HTTP clients `CurlClient` and `StreamClient` are invoked with a `ResponseFactoryInterface` instance 
as the first parameter, followed by optional `HTTPOptions` and PSR-3 `LoggerInterface` instances.
You can then send a request via the implemented PSR-18 method `ClientInterface::sendRequest()`,
using a PSR-7 `RequestInterface` and expect a PSR-7 `ResponseInterface`.

### `CurlClient`, `StreamClient`

```php
$options             = new HTTPOptions;
$options->ca_info    = '/path/to/cacert.pem';
$options->user_agent = 'my cool user agent 1.0';

$httpClient    = new CurlClient($responseFactory, $options, $logger);
$request = $requestFactory->createRequest('GET', 'https://www.example.com?foo=bar');

$httpClient->sendRequest($request);
```


### `CurlMultiClient`

The `CurlMultiClient` client implements asynchronous multi requests (["rolling-curl"](https://code.google.com/archive/p/rolling-curl/)).
It needs a `MultiResponseHandlerInterface` that parses the incoming responses, the callback may return a failed request to the stack:

```php
$handler = new class () implements MultiResponseHandlerInterface{

	public function handleResponse(
		ResponseInterface $response, // the incoming response
		RequestInterface $request,   // the corresponding request
		int $id,                     // the request id
		array $curl_info ,           // the curl_getinfo() result for this request
	):RequestInterface|null{
	
		if($response->getStatusCode() !== 200){
			// return the failed request back to the stack
			return $request;
		}
		
		try{
			$body = $response->getBody();
			
			// the response body is empty for some reason, we pretend that's fine and exit
			if($body->getSize() === 0){
				return null;
			}
			
			// parse the response body, store the result etc.
			$data = $body->getContents();
			
			// save data to file, database or whatever...
			// ...
	
		}
		catch(Throwable){
			// something went wrong, return the request to the stack for another try
			return $request;
		}
		
		// everything ok, nothing to return
		return null;
	}

};
```

You can then invoke the multi request client - the `MultiResponseHandlerInterface` and `ResponseFactoryInterface` are mandatory, 
`HTTPOptions` and `LoggerInterface` are optional:

```php
$options              = new HTTPOptions;
$options->ca_info     = '/path/to/cacert.pem';
$options->user_agent  = 'my cool user agent 1.0';
$options->sleep       = 750000; // microseconds, see usleep()
$options->window_size = 5;
$options->retries     = 1;

$multiClient = new CurlMultiClient($handler, $responseFactory, $options, $logger);

// create and add the requests
foreach(['..', '...', '....'] as $item){
	$multiClient->addRequest($factory->createRequest('GET', $endpoint.'/'.$item));
}

// process the queue
$multiClient->process();
```


### `URLExtractor`

The `URLExtractor` wraps a PSR-18 `ClientInterface` to extract and follow shortened URLs to their original location.

```php
$options                 = new HTTPOptions;
$options->user_agent     = 'my cool user agent 1.0';
$options->ssl_verifypeer = false;
$options->curl_options   = [
	CURLOPT_FOLLOWLOCATION => false,
	CURLOPT_MAXREDIRS      => 25,
];

$httpClient   = new CurlClient($responseFactory, $options, $logger);
$urlExtractor = new URLExtractor($httpClient, $responseFactory);

$request = $factory->createRequest('GET', 'https://t.co/ZSS6nVOcVp');

$urlExtractor->sendRequest($request); // -> response from the final location

// you can retrieve an array with all followed locations afterwards
$responses = $this->http->getResponses(); // -> ResponseInterface[]

// if you just want the URL of the final location, you can use the extract method: 
$url = $this->http->extract('https://t.co/ZSS6nVOcVp'); // -> https://api.guildwars2.com/v2/build
```


### `LoggingClient`
 
The `LoggingClient` wraps a `ClientInterface` and outputs the HTTP messages in a readable way through a `LoggerInterface` (do NOT use in production!).

```php
$loggingClient = new LoggingClient($httpClient, $logger);

$loggingClient->sendRequest($request); // -> log to output given via logger
```


### Auto generated API documentation

The API documentation can be auto generated with [phpDocumentor](https://www.phpdoc.org/).
There is an [online version available](https://chillerlan.github.io/php-httpinterface/) via the [gh-pages branch](https://github.com/chillerlan/php-httpinterface/tree/gh-pages) that is [automatically deployed](https://github.com/chillerlan/php-httpinterface/deployments) on each push to main.

Locally created docs will appear in the directory `.build/phpdocs/`. If you'd like to create local docs, please follow these steps:

- [download phpDocumentor](https://github.com/phpDocumentor/phpDocumentor/releases) v3+ as .phar archive
- run it in the repository root directory:
	- on Windows `c:\path\to\php.exe c:\path\to\phpDocumentor.phar --config=phpdoc.xml`
	- on Linux just `php /path/to/phpDocumentor.phar --config=phpdoc.xml`
- open [index.html](./.build/phpdocs/index.html) in a browser
- profit!


## Disclaimer

Use at your own risk!
