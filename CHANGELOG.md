# Changelog

## [1.1.0](https://github.com/deviantintegral/har/compare/v1.0.1...v1.1.0) (2026-01-20)


### Features

* add HarRecorder PSR-18 client decorator for recording HTTP traffic ([#245](https://github.com/deviantintegral/har/issues/245)) ([c32600d](https://github.com/deviantintegral/har/commit/c32600dfc942eea6406350c1f64f86fbcb37555e))
* **Log:** add entry filtering methods for test mocking ([#242](https://github.com/deviantintegral/har/issues/242)) ([1833b5b](https://github.com/deviantintegral/har/commit/1833b5b384eb7de29d04485c70f5a7dc0da49137))
* **renovate:** add prCreation and internalChecksFilter options ([18d4b0c](https://github.com/deviantintegral/har/commit/18d4b0c1a9ca858b3194a284446a84bac8726c77))


### Bug Fixes

* configure renovate to track fix-renovate-version branch ([e8f3273](https://github.com/deviantintegral/har/commit/e8f32732825e093533ea7434d47c765dbc73a0b6))
* disable strict mode in renovate config validator ([42dbd0b](https://github.com/deviantintegral/har/commit/42dbd0b0355ade7775f402f32c6e514d528066fe))
* point to our fork to fix config validation ([b393f93](https://github.com/deviantintegral/har/commit/b393f93953f804fc9c45989ae7c3638c32463dd9))
* remove followTag config as it doesn't work with branches ([#254](https://github.com/deviantintegral/har/issues/254)) ([e211e93](https://github.com/deviantintegral/har/commit/e211e937c4a13ceaab930a7f4d43f149c5497547))


### Miscellaneous Chores

* auto-merge all dependency updates after 3 day wait ([ab7271d](https://github.com/deviantintegral/har/commit/ab7271d5c4ecabdef802dae518c402afb9c5f03a))
* **config:** migrate config renovate.json ([7763d5a](https://github.com/deviantintegral/har/commit/7763d5af5da300efbdbc207b5e5ffdebc2f8e6de))
* **deps:** update actions/cache digest to 8b402f5 ([#257](https://github.com/deviantintegral/har/issues/257)) ([93389b0](https://github.com/deviantintegral/har/commit/93389b00b63ebbf4c5de49964ba1cb43a63da4e3))
* **deps:** update dependency friendsofphp/php-cs-fixer to v3.92.5 ([#240](https://github.com/deviantintegral/har/issues/240)) ([34f6e67](https://github.com/deviantintegral/har/commit/34f6e67c17d7f09d0a5cb42a35a672d72fcc7908))
* **deps:** update dependency infection/infection to v0.32.1 ([2905614](https://github.com/deviantintegral/har/commit/2905614c9c4dac9c6d7d8b444fe2db79225e1d6e))
* **deps:** update dependency infection/infection to v0.32.2 ([2e311b9](https://github.com/deviantintegral/har/commit/2e311b95af3d8c2e9a24cb561989ee156a87b391))
* **deps:** update dependency infection/infection to v0.32.3 ([34f78fa](https://github.com/deviantintegral/har/commit/34f78fab5d9ff80acd87412ca61d9c4516821e18))
* **deps:** update ggilder/codecoverage digest to ae4850f ([#258](https://github.com/deviantintegral/har/issues/258)) ([ea19a7e](https://github.com/deviantintegral/har/commit/ea19a7e0047dd07847efda246746f20541288b2a))
* **deps:** update suzuki-shunsuke/github-action-renovate-config-validator digest to c22827f ([146c9e2](https://github.com/deviantintegral/har/commit/146c9e255767ce52b106a4c4a059dcf46a74f190))
* **deps:** update suzuki-shunsuke/github-action-renovate-config-validator digest to ca480cb ([#256](https://github.com/deviantintegral/har/issues/256)) ([9731e9d](https://github.com/deviantintegral/har/commit/9731e9d18088df44b246c6505ebe7666880e6c82))
* switch renovate-config-validator to official v2.0.0 ([#255](https://github.com/deviantintegral/har/issues/255)) ([323bb6c](https://github.com/deviantintegral/har/commit/323bb6cbb54f84874aa3442e611789cd8350cbfa))
* use local XSD for PHPUnit configuration ([bcb4bdd](https://github.com/deviantintegral/har/commit/bcb4bdde6a61d4dd27ed6f88455ea2f5a5c371ca))

## [1.0.1](https://github.com/deviantintegral/har/compare/v1.0.0...v1.0.1) (2026-01-05)


### Bug Fixes

* **ci:** resolve nightly workflow permission error ([#230](https://github.com/deviantintegral/har/issues/230)) ([992ea1a](https://github.com/deviantintegral/har/commit/992ea1ae34f1a2b9bb998d7ffbbffde80be36997))
* **ci:** skip coverage annotation in nightly workflow ([#229](https://github.com/deviantintegral/har/issues/229)) ([cf85abe](https://github.com/deviantintegral/har/commit/cf85abef9eeaab58fa33b158b2265f61ac4601c6))
* **deps:** sync actionlint versions and update Renovate config ([a438df1](https://github.com/deviantintegral/har/commit/a438df166f6e2747d9499d6f61ebd58d41f2e2bd))


### Miscellaneous Chores

* **deps:** downgrade actionlint to v1.7.9 for Renovate testing ([bdc7f96](https://github.com/deviantintegral/har/commit/bdc7f96da80c74d96a9ffdce90ce1531a05afe8a))
* **deps:** pin actions/checkout action to 34e1148 ([#227](https://github.com/deviantintegral/har/issues/227)) ([ce6ce55](https://github.com/deviantintegral/har/commit/ce6ce556656b5350843e871e52b28e349a40eed6))
* **deps:** update actions/checkout action to v6 ([#228](https://github.com/deviantintegral/har/issues/228)) ([534d506](https://github.com/deviantintegral/har/commit/534d506afee769f85f9fbe9fbb2c9f99409ac97a))
* **deps:** update dependency friendsofphp/php-cs-fixer to v3.92.4 ([#232](https://github.com/deviantintegral/har/issues/232)) ([83d463e](https://github.com/deviantintegral/har/commit/83d463ecf04f0a0abf7f18e332c96e76106b8494))
* **deps:** update dependency infection/infection to v0.32.0 ([b1289be](https://github.com/deviantintegral/har/commit/b1289bebf77d5a0e48f592b4759625eed80a3e0d))
* **deps:** update dependency rhysd/actionlint to v1.7.10 ([8c2bfc9](https://github.com/deviantintegral/har/commit/8c2bfc90d22a023ce9b441c4f96526189106ab9b))
* **deps:** update dependency rhysd/actionlint to v1.7.10 ([#231](https://github.com/deviantintegral/har/issues/231)) ([aa0ff05](https://github.com/deviantintegral/har/commit/aa0ff057efbaf97998fb915b3eb7df1470bd2c0d))

## [1.0.0](https://github.com/deviantintegral/har/compare/v0.7.2...v1.0.0) (2025-12-18)


### Miscellaneous Chores

* **config:** migrate config renovate.json ([64d94b1](https://github.com/deviantintegral/har/commit/64d94b1c466402e52bafa36bd52eebee6883ab0b))
* **deps:** update dependency friendsofphp/php-cs-fixer to v3.92.3 ([#223](https://github.com/deviantintegral/har/issues/223)) ([5e0c76e](https://github.com/deviantintegral/har/commit/5e0c76eb88a2e8ff42d7a021ae285ea7adca0568))
* **main:** prepare to release 1.0.0 ([d245e5d](https://github.com/deviantintegral/har/commit/d245e5da337f74d2fd6e10d5e043e075cb6b8d14))

## [0.7.2](https://github.com/deviantintegral/har/compare/v0.7.1...v0.7.2) (2025-12-18)


### Miscellaneous Chores

* **deps:** update softprops/action-gh-release digest to a06a81a ([dfe023f](https://github.com/deviantintegral/har/commit/dfe023f1494fe08e6c9b14235590e16d61e25d97))

## [0.7.1](https://github.com/deviantintegral/har/compare/v0.7.0...v0.7.1) (2025-12-18)


### Miscellaneous Chores

* add infection.phar.asc to gitignore ([8998321](https://github.com/deviantintegral/har/commit/89983217f1ae4469e19218079cd495f70bed92dc))
* **main:** actually let's not do 1.0.0 yet ([fa424fb](https://github.com/deviantintegral/har/commit/fa424fb9c358915dedd0d76295d126fe10b2073d))
* **main:** prepare for 1.0.0 ([f4f84f8](https://github.com/deviantintegral/har/commit/f4f84f8e215b50047ec62d50a4fbcf331e725e8a))

## [0.7.0](https://github.com/deviantintegral/har/compare/v0.6.0...v0.7.0) (2025-12-17)


### Features

* make SessionStart hook only run in remote environments ([9a42b73](https://github.com/deviantintegral/har/commit/9a42b73fab5e792f0a7e28c4fa6a83a56fe905b0))


### Bug Fixes

* validate required 'log' key during HAR deserialization ([9d938fb](https://github.com/deviantintegral/har/commit/9d938fb0ee3a8642e9647d27296c3228353dcb7a))


### Miscellaneous Chores

* add phpunit.xml to .gitignore ([79e46f1](https://github.com/deviantintegral/har/commit/79e46f151ae6b4f6b1664b68503c613cc517db46))
* configure Renovate to track Infection version ([6a7ef28](https://github.com/deviantintegral/har/commit/6a7ef286dbe14c6ac049f1b10d4ab291d4c8134a))
* **deps:** update dependency friendsofphp/php-cs-fixer to v3.92.2 ([#162](https://github.com/deviantintegral/har/issues/162)) ([be5e7f1](https://github.com/deviantintegral/har/commit/be5e7f1092ffe1da784245dc8db02e2b064f8937))
* **deps:** update dependency infection/infection to v0.31.9 ([c419fe6](https://github.com/deviantintegral/har/commit/c419fe6a74980ed0a46ed2d53c803adcdc082f51))
* ignore equivalent LessThan mutation in HarFileRepository::getIds ([4f0e2a0](https://github.com/deviantintegral/har/commit/4f0e2a00ca36ae4fa0763ac4249276641d5bd74a))
* ignore equivalent ReturnRemoval mutation in HarFileRepository::getIds ([72b5839](https://github.com/deviantintegral/har/commit/72b583932a3aac4edddda0da934fb45b41a80d27))
* ignore equivalent YieldValue mutation in Har::splitLogEntries ([8eaf8fb](https://github.com/deviantintegral/har/commit/8eaf8fb70eb015c70d98b880e46dcb52531a950d))
* update renovate to track infection in SessionStart hook ([914153d](https://github.com/deviantintegral/har/commit/914153dae169063b384678900a41a032aeb24f88))

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
