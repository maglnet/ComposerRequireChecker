# ComposerRequireChecker

A CLI tool to analyze composer dependencies and verify that no unknown symbols are used in the sources of a package.
This will prevent you from using "soft" dependencies that are not defined within your `composer.json` require section.

[![PHP ^7.2](https://img.shields.io/badge/PHP-^7.2-brightgreen.svg?style=flat-square)](https://php.net)
[![current version](https://img.shields.io/packagist/v/maglnet/composer-require-checker.svg?style=flat-square)](https://packagist.org/packages/maglnet/composer-require-checker)
[![Build Status](https://img.shields.io/travis/maglnet/ComposerRequireChecker.svg?style=flat-square)](https://travis-ci.org/maglnet/ComposerRequireChecker)
[![Code Coverage](https://scrutinizer-ci.com/g/maglnet/ComposerRequireChecker/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/maglnet/ComposerRequireChecker/?branch=master)

## What's it about?

Most likely your code relies on external dependencies; these can be *hard* or *soft*.

As an example, imagine you are extending the `TestCase` class from *PHPUnit*, in your project. You probalby called `composer require --dev phpunit/phpunit` to add the library under `require-dev` in `composer.json`, so to ensure that your code knows where to find that `TestCase` class. This means that your dependency is a *"hard dependency"*.

But what if you did a `composer require phpunit/dbunit` instead? Since *DBUnit* also [requires *phpunit/phpunit* in its `composer.json`](https://github.com/sebastianbergmann/dbunit/blob/master/composer.json), all your dependencies would still work, wouldn't they? Well, they might; but only out of sheer luck (as in this far fetched example).

*DBunit*'s dependency on *PHPUnit* `TestCase` class is not immediately obvious, when looking at your `composer.json`. The dependency might be hidden as `suggest`ed in another dependency. That's what a *"soft dependency"* is. And you should avoid them, as they might blow up.
Imagine the maintainer of *DBUnit* (that currently includes *PHPUnit*, with the `TestCase` class) switching to a completely different library than *PHPUnit*. Suddenly your code would break after a `composer update`, for no apparent reason. *PHPUnit* was removed by the update as obsolete, together with its `TestCase` class that you can't extended anymore in your project. Hence you'll get a fatal `Uncaught Error: Class 'TestCase' not found` error. This, just because you didn't include it as a hard *"first level dependency"*.

This *CLI* parses your code and your `composer.json` to check whether your code contains such *"soft dependencies"* that might break your code.

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

You can now adjust this file, as needed, and tell composer-require-checker to use it for its configuration.

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

## Usage

Composer require checker runs on an existing directory structure. It does not change your code, and does not even install your composer dependencies. That is a task that is entirely up to you, allowing you to change/improve things after a scan to see if it fixes the issue.

So the usual workflow would be

1. Clone your repo
2. `composer install` your dependencies
3. `composer-require-checker check` your code

### Dealing with custom installer plugins

Composer require checker only fetches its knowledge of where files are from your project's `composer.json`. It does not use Composer itself to understand custom directory structures.

If your project requires to use any install plugins to put files in directories that are not `vendor/` or defined via the `vendor-dir` config setting in `composer.json`, composer require checker will fail to detect the required code correctly.

As a workaround, you can install your dependencies without plugins just for the scan:

1. Clone your repo
2. `composer install --no-plugins` will put all code into the `vendor` folder
3. `composer-require-checker check` your code
4. `composer install` dependencies once again in the correct location

## License

This package is made available under the [MIT LICENSE](LICENSE).

## Credits

This package was initially designed by [Marco Pivetta](https://github.com/ocramius) and [Matthias Glaub](https://github.com/maglnet).  
And of course all [Contributors](https://github.com/maglnet/ComposerRequireChecker/graphs/contributors).

[Composer]: https://getcomposer.org
[Composer cli - global]: https://getcomposer.org/doc/03-cli.md#global
