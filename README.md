# ComposerRequireChecker

A CLI tool to analyze composer dependencies and verify that no unknown symbols are used in the sources of a package.
This will prevent you from using "soft" dependencies that are not defined within your `composer.json` require section.

[![PHP ~7.1](https://img.shields.io/badge/PHP-~7.1-brightgreen.svg?style=flat-square)](https://php.net)
[![current version](https://img.shields.io/packagist/v/maglnet/composer-require-checker.svg?style=flat-square)](https://packagist.org/packages/maglnet/composer-require-checker)
[![Build Status](https://img.shields.io/travis/maglnet/ComposerRequireChecker.svg?style=flat-square)](https://travis-ci.org/maglnet/ComposerRequireChecker)
[![Dependency Status](https://www.versioneye.com/user/projects/565df3b9b6f5ff00380001ea/badge.svg?style=flat)](https://www.versioneye.com/user/projects/565df3b9b6f5ff00380001ea)
[![Code Coverage](https://scrutinizer-ci.com/g/maglnet/ComposerRequireChecker/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/maglnet/ComposerRequireChecker/?branch=master)

## What's it about?

Your code most certainly uses external dependencies. And if it's only extending PHPUnits `TestCase`. And to make sure that your code knows where to find that `TestCase`-class you for sure called `composer require --dev phpunit/phpunit`. That means your dependency is a hard dependency. 

But what if you did a `composer require phpunit/dbunit`? As that package requires phpunit/phpunit as well all your dependencies would still work, wouldn't they? But only out of sheer luck (and in this far fetched example because it's the way it's designed). But from your `composer.json` one wouldn't immediately know that you'd use PHPUnits `TestCase`-class. That's what a "soft dependency" is. And you should avoid those as they might blow. Imagine the person maintaining the library that also includes the package that you depend on suddenly using a completely different library. Suddenly your code would break after a `composer update` for no apparent reason. Just because you didn't include the dependency as a "first level" dependency in the first place.

This CLI-Tool parses your code and your composer.json-file to see whether your code contains such "soft dependencies" that might break your code.

## Installation / Usage

Composer require checker is not supposed to be installed as part of your project dependencies.
  
### PHAR file [preferred]

Please check the [releases](https://github.com/maglnet/ComposerRequireChecker/releases) for available phar files.
Download the latest release and and run it like this:
```
php composer-require-checker.phar check /path/to/your/project/composer.json
```

### PHIVE

If you already use [PHIVE](https://phar.io/)  to install and manage your project’s tooling, then you should be able to simply install ComposerRequireChecker like this:

```
phive install composer-require-checker
``` 

### Composer - global command

This package can be easily globally installed by using [Composer]:

```sh
composer global require maglnet/composer-require-checker
```

If you haven't already setup you composer installation to support global requirements, please refer to the [Composer cli - global]
If this is already done, run it like this:

```
composer-require-checker check /path/to/your/project/composer.json
```

## Configuration

Composer require checker is configured to whitelist some symbols per default. Have a look at the
[config file example](data/config.dist.json) to see which configuration options are available.

You can now adjust this file, as needed, and tell composer-require-checker to use it for it's configuration.

```sh
bin/composer-require-checker check --config-file=path/to/config.json /path/to/your/project/composer.json
``` 

### Scan Additional Files

To scan files, that are not part of your autoload definition you may add glob patterns to the config file's `scan-files`
section.

The following example would also scan the file `bin/console` and all files with `.php` extension within your `bin/` folder:

```
"scan-files" : ["bin/console", "bin/*.php"]
```

## License

This package is made available under the [MIT LICENSE](LICENSE).

## Credits

This package was initially designed by [Marco Pivetta](https://github.com/ocramius) and [Matthias Glaub](https://github.com/maglnet).  
And of course all [Contributors](https://github.com/maglnet/ComposerRequireChecker/graphs/contributors).

[Composer]: https://getcomposer.org
[Composer cli - global]: https://getcomposer.org/doc/03-cli.md#global
