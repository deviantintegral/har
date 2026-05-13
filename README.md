# HTTP Archive for PHP

[![CI](https://github.com/deviantintegral/har/actions/workflows/ci.yml/badge.svg)](https://github.com/deviantintegral/har/actions/workflows/ci.yml) [![Packagist](https://img.shields.io/packagist/dt/deviantintegral/har?style=flat-square)](https://packagist.org/packages/deviantintegral/har)

## Requirements

* PHP 8.2+
* The `ext-json` extension.

## Installation

`composer require deviantintegral/har`, or
[download the CLI tool](https://github.com/deviantintegral/har/releases).

## Introduction

This library supports reading and writing [HTTP Archive](http://www.softwareishard.com/blog/har-12-spec/) files. These
archives are JSON objects containing one or more HTTP request and response pairs. In particular, this library is useful
for taking HTTP requests exported from a browser's developer tools or with a proxy like
[mitmproxy](https://mitmproxy.org) and using them as mocks in tests.

Features include:

* Reading a HAR into a fully-typed set of classes.
* Writing a `\Deviantintegral\Har\Har` back out into a HAR JSON string.
* Adapters for PSR-7 Request and Response interfaces.
* An interface and `\Deviantintegral\Har\HarRepository` class to load HARs from a filesystem or other backend.
* [A CLI tool](https://github.com/deviantintegral/har/releases) to split a HAR file into single files per request / response pair.
* Redacting sensitive values (headers, cookies, query parameters, and JSON body fields) before sharing a HAR.

## Example

See [ReadmeTest.php](tests/src/Unit/ReadmeTest.php) for an example of how to use this library.

## Redacting sensitive data

HAR files captured from browsers or proxies often contain credentials, session
cookies, or other secrets. Use `HarSanitizer` to replace those values with
`[REDACTED]` (configurable via `setRedactedValue()`) before sharing the file.
Field matching is case-insensitive by default.

```php
use Deviantintegral\Har\HarSanitizer;

$sanitized = (new HarSanitizer())
    ->redactHeaders(['Authorization', 'Cookie'])
    ->redactCookies(['session'])
    ->redactQueryParams(['api_key'])
    ->redactBodyFields(['password', 'token'])
    ->sanitize($har);
```

The CLI ships a `har:sanitize` command that exposes the same options:

```
bin/console har:sanitize input.har output.har \
    --header=Authorization --header=Cookie \
    --query-param=api_key \
    --body-field=password
```

## Optional values

The HAR specification documents some fields as `-1` if they do not have a
value. Other fields, like `comment`, may be omitted.

Fields that may be omitted will have a `has` method that should be called
before calling `get`. For integer fields, the return value must be checked for
`-1`.

## Fidelity of Serialized and Deserialized data

This library aims to preserve the actual JSON representation of loaded objects. However, in some cases, this is not possible. In
particular, PHP only supports up to 6 digits in ISO 8601 timestamps, so any additional precision is lost. See
[HarTest](tests/src/Unit/HarTest.php) for an example that checks the reading and writing of a HAR.
