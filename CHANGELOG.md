# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 3.3.2 - TBD

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.3.1 - 2021-11-08


-----

### Release Notes for [3.3.1](https://github.com/maglnet/ComposerRequireChecker/milestone/17)

3.3.x bugfix release (patch)

### 3.3.1

- Total issues resolved: **1**
- Total pull requests resolved: **1**
- Total contributors: **2**

#### bug

 - [310: For unix-y phar file, always use unix-y line endings](https://github.com/maglnet/ComposerRequireChecker/pull/310) thanks to @mfn
 - [281: PHAR 3.3.0 has \r char that breaks tool under Linux](https://github.com/maglnet/ComposerRequireChecker/issues/281) thanks to @Slamdunk

## 3.3.0 - 2021-06-09


-----

### Release Notes for [3.3.0](https://github.com/maglnet/ComposerRequireChecker/milestone/15)

Feature release (minor)

### 3.3.0

- Total issues resolved: **1**
- Total pull requests resolved: **10**
- Total contributors: **3**

#### enhancement

 - [273: Catch PHP Parser error and rethrow it with file information](https://github.com/maglnet/ComposerRequireChecker/pull/273) thanks to @bobvandevijver

#### dependencies

 - [261: Bump vimeo/psalm from 4.6.4 to 4.7.0](https://github.com/maglnet/ComposerRequireChecker/pull/261) thanks to @dependabot[bot]
 - [260: Bump symfony/console from 5.2.5 to 5.2.6](https://github.com/maglnet/ComposerRequireChecker/pull/260) thanks to @dependabot[bot]
 - [258: Bump phpunit/phpunit from 9.5.0 to 9.5.4](https://github.com/maglnet/ComposerRequireChecker/pull/258) thanks to @dependabot[bot]
 - [257: Bump vimeo/psalm from 4.3.2 to 4.6.4](https://github.com/maglnet/ComposerRequireChecker/pull/257) thanks to @dependabot[bot]
 - [254: Bump symfony/console from 5.2.1 to 5.2.5](https://github.com/maglnet/ComposerRequireChecker/pull/254) thanks to @dependabot[bot]
 - [253: Bump phpstan/phpstan from 0.12.64 to 0.12.81](https://github.com/maglnet/ComposerRequireChecker/pull/253) thanks to @dependabot[bot]
 - [247: Bump phpunit/phpunit from 9.5.0 to 9.5.2](https://github.com/maglnet/ComposerRequireChecker/pull/247) thanks to @dependabot-preview[bot]
 - [246: Bump phpstan/phpstan from 0.12.64 to 0.12.80](https://github.com/maglnet/ComposerRequireChecker/pull/246) thanks to @dependabot-preview[bot]
 - [241: Bump webmozart/glob from 4.2.0 to 4.3.0](https://github.com/maglnet/ComposerRequireChecker/pull/241) thanks to @dependabot-preview[bot]

## [2.1.0] - 2019-12-28
### Added
- Add support of Symfony Console 5. - #174
- Test on PHP 7.4 - #164

### Changed
- updated a lot of dependencies within the phar file
- Require ext-zend-opcache for development. - #161 #160
- Invalid version reported when installed without .git - #109 
- Correctly report tool version in cli - #146
- Fix: Configure path to PHPUnit result cache file - #131 
- Enhancement: Clean up .gitignore - #132 
- Fix: Badges - #133 
- Fix: Remove file headers - #129 
- Enhancement: Mark test classes as final - #130 
- Enhancement: Update phpunit/phpunit - #126 
- Enhancement: Sort unknown symbols - #117 
- Fix: Drop support for PHP 7.1 - #127 
- Enhancement: Collect coverage only when actually desired - #121 
- Fix: Reduce visibility of setUp() - #123 
- Enhancement: Keep packages sorted in composer.json - #124 
- Enhancement: Reference phpunit.xsd as installed with composer - #125 
- Enhancement: Add void return type declarations to test methods - #118 
- Fix: Do not update composer itself twice - #119 
- Fix: Exclude test fixtures from scrutiny of Scrutinizer - #120 
- Enhancement: Normalize composer.json - #122 
- Don't load composer.json file twice - #116

## [2.0.0] - 2019-03-19
### Added
- add symbol counts to check command for verbose output - #90
- suggest `php` as extension if it's a core extension - #103
- ensure binary returns correct exit code - #89 #98 #107
- normalize extension names - #99 #100

### Changed
- use `installed.json` instead of `composer.json`

## [1.1.0] - 2018-09-03
### Added
- add possibility to add additional paths/files to be scanned (see [webmozart/glob](https://github.com/webmozart/glob) for valid pattern)
  see `scan-files` key within `config.dist.json` (fixes #76, #77)
- dynamically detects vendor dir from `composer.json` `vendor-dir` setting (#85)
- core extensions get recognised for `php-64bit` (#80)

### Changed
- only using stable dependencies now (#86)

## [1.0.0] - 2018-07-11
### Changed
- update `nikic/php-parser` to `~4.0` (#75)

## [0.2.1] - 2018-03-20
### Added
- `object` keyword (introduced in PHP 7.2) is now whitelisted (#61) 

### Changed
- fixes recording of constants that are defined by the function `define()` (#55)
- fixes usage of `exclude-from-classmap` key of composer.json (#60)
- fixed several docblocks 

## [0.2.0] - 2018-01-20
### Changed
- PHP 7.1 required
- displays more detailed errors if json config format is not valid
- fixed fatal error when parsing trait usage with modified visibility (#44)
- when locating files "exclude-from-classmap" will be taken into account
- add option "--ignore-parse-errors"
  ComposerRequireChecker will not throw parser exceptions if this is set (#35)

## [0.1.6] - 2017-09-24
### Added
- add shebang `#!/usr/bin/env php` to phar file / allows direct execution of phar file 

## [0.1.5] - 2017-07-23
### Added
- added builtin phar extension to default list
- fixes problems when parsing anonymous classes (#23)

## [0.1.4] - 2017-05-13
### Changed
- fixes problems when provided composer.json path was absolute

## [0.1.3] - 2017-05-13
### Added
- CHANGELOG
- support for PHP 7.1

### Changed
- using nikic/php-parser 3.0
- use PHPUnit 6.0 for testing
- fixes problems with relative paths when used as phar file

## [0.1.2] - 2016-05-17
### Added
- add signing to phar file creation

## [0.1.1] - 2015-12-02

## [0.1.0] - 2015-12-02

[Unreleased]: https://github.com/maglnet/ComposerRequireChecker/compare/2.1.0...HEAD
[2.1.0]: https://github.com/maglnet/ComposerRequireChecker/compare/2.0.0...2.1.0
[2.0.0]: https://github.com/maglnet/ComposerRequireChecker/compare/1.1.0...2.0.0
[1.1.0]: https://github.com/maglnet/ComposerRequireChecker/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/maglnet/ComposerRequireChecker/compare/0.2.1...1.0.0
[0.2.1]: https://github.com/maglnet/ComposerRequireChecker/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/maglnet/ComposerRequireChecker/compare/0.1.6...0.2.0
[0.1.6]: https://github.com/maglnet/ComposerRequireChecker/compare/0.1.5...0.1.6
[0.1.5]: https://github.com/maglnet/ComposerRequireChecker/compare/0.1.4...0.1.5
[0.1.4]: https://github.com/maglnet/ComposerRequireChecker/compare/0.1.3...0.1.4
[0.1.3]: https://github.com/maglnet/ComposerRequireChecker/compare/0.1.2...0.1.3
[0.1.2]: https://github.com/maglnet/ComposerRequireChecker/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/maglnet/ComposerRequireChecker/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/maglnet/ComposerRequireChecker/compare/8ea36556ad0ccb0618391cff6c1dd53e1e07486f...0.1.0
