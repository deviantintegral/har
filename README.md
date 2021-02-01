# HTTP Archive for PHP

[![CircleCI](https://circleci.com/gh/deviantintegral/har.svg?style=svg)](https://circleci.com/gh/deviantintegral/har) [![Packagist](https://img.shields.io/packagist/dt/deviantintegral/har?style=flat-square)](https://packagist.org/packages/deviantintegral/har)

## Requirements

* PHP 7.3+
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

## Example

See [ReadmeTest.php](tests/src/Unit/ReadmeTest.php) for an example of how to use this library.

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
