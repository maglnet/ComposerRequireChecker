# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [1.0.0] - 2018-07-11
### Added

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
### Added

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

### Changed

## [0.1.5] - 2017-07-23
### Added
- added builtin phar extension to default list
- fixes problems when parsing anonymous classes (#23)

### Changed

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
