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
[![phpDocs][gh-docs-badge]][gh-docs]

[php-badge]: https://img.shields.io/packagist/php-v/chillerlan/php-httpinterface?logo=php&color=8892BF
[php]: https://www.php.net/supported-versions.php
[packagist-badge]: https://img.shields.io/packagist/v/chillerlan/php-httpinterface.svg
[packagist]: https://packagist.org/packages/chillerlan/php-httpinterface
[license-badge]: https://img.shields.io/github/license/chillerlan/php-httpinterface.svg
[license]: https://github.com/chillerlan/php-httpinterface/blob/main/LICENSE
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
[gh-docs-badge]: https://github.com/chillerlan/php-httpinterface/workflows/Docs/badge.svg
[gh-docs]: https://github.com/chillerlan/php-httpinterface/actions?query=workflow%3ADocs

# Documentation

See [the wiki](https://github.com/chillerlan/php-httpinterface/wiki) for advanced documentation.
An API documentation created with [phpDocumentor](https://www.phpdoc.org/) can be found at https://chillerlan.github.io/php-httpinterface/ (WIP).

## Requirements
- PHP 7.4+
  - the cURL extension

## Installation
**requires [composer](https://getcomposer.org)**

*composer.json* (note: replace `dev-main` with a [version boundary](https://getcomposer.org/doc/articles/versions.md))
```json
{
	"require": {
		"php": "^7.4",
		"chillerlan/php-httpinterface": "dev-main"
	}
}
```

Profit!

