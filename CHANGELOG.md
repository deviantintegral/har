# Changelog

## [0.6.0](https://github.com/deviantintegral/har/compare/v0.5.0...v0.6.0) (2025-12-16)


### Features

* add --version flag with build time ([#127](https://github.com/deviantintegral/har/issues/127)) ([870c439](https://github.com/deviantintegral/har/commit/870c43956fe6a7ad1349408b21dbbf8efa45c7f1))
* add hasConnect() and hasSsl() methods to Timings ([1ebb103](https://github.com/deviantintegral/har/commit/1ebb1036df34d9f6b3a282bb749c51552622e78a))
* add missing return type declarations ([41cfd7d](https://github.com/deviantintegral/har/commit/41cfd7d4abb336427e1903d0688a106b17e287f1))


### Bug Fixes

* add missing type specifications to test code ([1749a9d](https://github.com/deviantintegral/har/commit/1749a9db8f45b15bacfdd1b8c31c3f89073941b5))
* correct parameter type in PageTimings.setOnContentLoad() ([ce5ebb4](https://github.com/deviantintegral/har/commit/ce5ebb4482a75b488f9f5b219fa342284b3a8cb6))
* correct PHPDoc return types to match implementation ([5ab1a8b](https://github.com/deviantintegral/har/commit/5ab1a8ba115d950b8116967e9d6520ea6a7a4243))
* implement deep clone for Har and Log objects ([#138](https://github.com/deviantintegral/har/issues/138)) ([41bef31](https://github.com/deviantintegral/har/commit/41bef3130fb92d27a5693d954469616bc38d3295))
* make withoutHeader() case-insensitive per PSR-7 spec ([2d386f7](https://github.com/deviantintegral/har/commit/2d386f70982dc3bead41582693ceec11406cbe06))
* remove redundant type checks([#150](https://github.com/deviantintegral/har/issues/150)) ([f1693fc](https://github.com/deviantintegral/har/commit/f1693fc6b674e8075a7629c8964edb8929858a28))
* remove trailing commas from single-parameter method signatures ([f7260be](https://github.com/deviantintegral/har/commit/f7260bebd823cde5814fc54c7d381e93d4637390))
* replace deprecated stream_for() with Utils::streamFor() ([638c6cd](https://github.com/deviantintegral/har/commit/638c6cd1bf54957f0e9dd63b4517fc43e6612831))
* resolve PHPStan already narrowed type errors ([3781f5c](https://github.com/deviantintegral/har/commit/3781f5cc88a07c19bb5617397c4a7cf7334f78a5))
* resolve PHPStan contravariance errors in PSR-7 adapters ([efe975b](https://github.com/deviantintegral/har/commit/efe975b60f6b38f2a82896384e0c22df7ce2e7f2))
* resolve unsafe usage of new static() PHPStan errors ([b3632ed](https://github.com/deviantintegral/har/commit/b3632ed3b4e5f810a178b981ba56213ac06b72b8))
* suppress PHPStan template type error in test ([431135e](https://github.com/deviantintegral/har/commit/431135eefd5a9745a332465410fae21c47a260b5))
* use null instead of empty array for clearing params in setText ([d890fa7](https://github.com/deviantintegral/har/commit/d890fa7113fa453b0f94b19652d6204b0e98ad6f))
* use static return type for fluent interface methods ([ecf2444](https://github.com/deviantintegral/har/commit/ecf24444346b19d840374ff19a3dc3983a0e2a5a))


### Miscellaneous Chores

* **deps:** update dependency friendsofphp/php-cs-fixer to v3.92.1 ([#145](https://github.com/deviantintegral/har/issues/145)) ([126262f](https://github.com/deviantintegral/har/commit/126262f3d350d0115bcf6c04fe82119e32251009))
* **phpstan:** mark narrowed type warning as OK ([378fedf](https://github.com/deviantintegral/har/commit/378fedff6ce170b3f742999bdd620864989f2c49))
* rename deprecated php-cs-fixer rulesets ([3a8f6cd](https://github.com/deviantintegral/har/commit/3a8f6cd9e8a67ca0e223e2eeec6e0a75816e9507))
* replace PECL with apt for xdebug installation ([#132](https://github.com/deviantintegral/har/issues/132)) ([0f293aa](https://github.com/deviantintegral/har/commit/0f293aa1f23f66c8a7a04bd21197dd423b140b94))
* update license identifier ([770e7cb](https://github.com/deviantintegral/har/commit/770e7cbefcbc7df904224bcb72d4a749078564a7))

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
