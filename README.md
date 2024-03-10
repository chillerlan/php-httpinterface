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

See [the wiki](https://github.com/chillerlan/php-httpinterface/wiki) for advanced documentation.
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


## Disclaimer

Use at your own risk!
