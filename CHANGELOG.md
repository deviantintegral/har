# Changelog

## [0.5.0](https://github.com/deviantintegral/har/compare/v0.4.0...v0.5.0) (2025-12-15)


### Features

* migrate from MacFJA/PharBuilder to Box ([#125](https://github.com/deviantintegral/har/issues/125)) ([b1187c2](https://github.com/deviantintegral/har/commit/b1187c22fe09fe8df141ad858934602d15fed78a))


### Bug Fixes

* add directory validation in getIds() before scandir() ([22057e0](https://github.com/deviantintegral/har/commit/22057e05cd479e84652aa8e28e7f0df8ff399960))
* add error handling for missing files in SplitCommand ([#122](https://github.com/deviantintegral/har/issues/122)) ([ed3fa82](https://github.com/deviantintegral/har/commit/ed3fa82f4cec0c5882d029e2840e78cd8b8b33a5))
* add type hints to SplitCommand::getSplitDestination parameters ([#123](https://github.com/deviantintegral/har/issues/123)) ([c62a75a](https://github.com/deviantintegral/har/commit/c62a75a7a0dae3a438ecce7123679f7d5c6a1966))
* check if a HAR repository exists before loading ([93fbb57](https://github.com/deviantintegral/har/commit/93fbb5720a93af18e91387bc22e9b8951f8c3ff2))
* correct inverted logic in hasBlocked() and hasDns() methods ([2285c23](https://github.com/deviantintegral/har/commit/2285c23df4c1ad7bd52c2a4fbcb6e9cfc0316fcc))
* use strict comparison (===) instead of weak comparison (==) in Serializer.php ([453d8f3](https://github.com/deviantintegral/har/commit/453d8f3d74271533abd9037f57798d94837ed0c7))


### Miscellaneous Chores

* add missing call to SessionStart ([#119](https://github.com/deviantintegral/har/issues/119)) ([51b50a3](https://github.com/deviantintegral/har/commit/51b50a3b8dcf01137ade67c33a93e74850a3693b))
* auto-install php-pear in SessionStart hook ([#116](https://github.com/deviantintegral/har/issues/116)) ([48acab5](https://github.com/deviantintegral/har/commit/48acab5a19e6586ba9edc11533756750aace0280))
* **deps:** pin ggilder/codecoverage action to 47c83da ([#118](https://github.com/deviantintegral/har/issues/118)) ([85aa78d](https://github.com/deviantintegral/har/commit/85aa78dec0dd21547cc1057f9c757cab8c3d3d60))
* fix newlines ([f5ded8c](https://github.com/deviantintegral/har/commit/f5ded8cb5d41ad83650ab3584c94746e7c25a96b))
* install xdebug for claude sessions ([54f3138](https://github.com/deviantintegral/har/commit/54f31389ece4119c09cfa47d60bbbd26da3ec59c))
* run composer install in claude ([233d999](https://github.com/deviantintegral/har/commit/233d999ff501fbd142b7499b890588498679f941))

## [0.4.0](https://github.com/deviantintegral/har/compare/0.3.0...v0.4.0) (2025-12-14)


### Features

* add pre-commit hooks for php-cs-fixer and phpunit ([#109](https://github.com/deviantintegral/har/issues/109)) ([80a12c4](https://github.com/deviantintegral/har/commit/80a12c4ea9b3fa705281268e8b47adfcbf79be7a))
* add support for PSR-7 ServerRequest ([#108](https://github.com/deviantintegral/har/issues/108)) ([7df2364](https://github.com/deviantintegral/har/commit/7df236497e9657c8e4fa878f267f211eab31cb46))
* drop support for PHP 8.0 and 8.1 ([#104](https://github.com/deviantintegral/har/issues/104)) ([2241b8d](https://github.com/deviantintegral/har/commit/2241b8d7177487a7f0702faeca1046e46d69cb76))


### Bug Fixes

* set release-please manifest version to 0.4.0 ([72ccbee](https://github.com/deviantintegral/har/commit/72ccbeebcc3501fa3e33f589ba98031f5cbf0b07))


### Miscellaneous Chores

* **config:** migrate Renovate config ([#98](https://github.com/deviantintegral/har/issues/98)) ([83d168d](https://github.com/deviantintegral/har/commit/83d168df7a5a26ecff77ec9d22ec5ad5b1bed978))
* **deps:** update actions/upload-artifact action to v6 ([#107](https://github.com/deviantintegral/har/issues/107)) ([54c890a](https://github.com/deviantintegral/har/commit/54c890a19a5b9cca3bfaa64dd8d03a98d7a5d3e8))
* fix nullable param deprecation ([#110](https://github.com/deviantintegral/har/issues/110)) ([79ced46](https://github.com/deviantintegral/har/commit/79ced460a071dcd59e6dfcc0ca98c56e7f9a273d))
* upgrade PHPUnit to ^11||^12 ([#106](https://github.com/deviantintegral/har/issues/106)) ([4617172](https://github.com/deviantintegral/har/commit/4617172b153b5d5935a344c82b22fa5bb3c6bc2c))
* upgrade symfony/console to ^7||^8 ([#105](https://github.com/deviantintegral/har/issues/105)) ([4002a2c](https://github.com/deviantintegral/har/commit/4002a2c28cf4733e6530329a3ca953e124ba9052))
