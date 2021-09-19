# ComposerRequireChecker

A CLI tool to analyze composer dependencies and verify that no unknown symbols are used in the sources of a package.
This will prevent you from using "soft" dependencies that are not defined within your `composer.json` require section.

[![PHP ^7.4](https://img.shields.io/badge/PHP-^7.4-brightgreen.svg?style=flat-square)](https://php.net)
[![current version](https://img.shields.io/packagist/v/maglnet/composer-require-checker.svg?style=flat-square)](https://packagist.org/packages/maglnet/composer-require-checker)
[![Build Status](https://img.shields.io/travis/maglnet/ComposerRequireChecker.svg?style=flat-square)](https://travis-ci.org/maglnet/ComposerRequireChecker)

## What's it about?

"Soft" (or transitive) dependencies are code that you did not explicitly define to be there, but use it nonetheless. The opposite is a "hard" (or direct) dependency.

Your code most certainly uses external dependencies. Imagine that you found a library to access a remote API. You require `thatvendor/api-lib` for your software and use it in your code. This library is a hard dependency.

Then you see that another remote API is available, but no library exists. The use case is simple, so you look around and find that `guzzlehttp/guzzle` (or any other HTTP client library) is already installed, and you use it right away to fetch some info. Guzzle just became a soft dependency.

Then some day, when you update your dependencies, your access to the second API breaks. Why? Turns out that the reason `guzzlehttp/guzzle` was installed is that it is a dependency of `thatvendor/api-lib` you included, and their developers decided to update from an earlier major version to the latest and greatest, simply stating in their changelog: "Version 3.1.0 uses the lates major version of Guzzle - no breaking changes expected."

And you think: What about my broken code?

Composer-require-checker parses your code and your composer.json-file to see whether your code uses symbols that are not declared as a required library, i.e. that are soft dependencies. If you rely on components that are already installed, but you didn't explicitly request them, this tool will complain about them, and you should require them explicitly, making them hard dependencies. This will prevent unexpected updates.

In the situation above you wouldn't get the latest update of `thatvendor/api-lib`, but your code would continue to work if you also required `guzzlehttp/guzzle` before the update.

The tool will also check for usage of PHP functions that are only available if an extension is installed, and will complain if that extension isn't explicitly required.

## Installation / Usage

Composer require checker is not supposed to be installed as part of your project dependencies.
  
### PHAR file [preferred]

Please check the [releases](https://github.com/maglnet/ComposerRequireChecker/releases) for available phar files.
[Download the latest release](https://github.com/maglnet/ComposerRequireChecker/releases/latest/download/composer-require-checker.phar) and and run it like this:
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

### A note about Xdebug

If your PHP is including Xdebug when running ComposerRequireChecker, you may experience additional issues like exceeding the Xdebug-related max-nesting-level - and on top, Xdebug slows PHP down.

It is recommended to run ComposerRequireChecker without Xdebug. 

If you cannot provide a PHP instance without Xdebug yourself, try setting an environment variable like this for just the command: `XDEBUG_MODE=off php composer-require-checker`.

## Configuration

Composer require checker is configured to whitelist some symbols per default. Have a look at the
[config file example](data/config.dist.json) to see which configuration options are available.

You can now adjust this file, as needed, and tell composer-require-checker to use it for it's configuration.

Note that you'll have to copy it's contents if you want to add something on top. This tool intentionally only 
reads one configuration file. If you pass only your new settings, you'll get error reports about the PHP core
extensions and internal symbols like `true` or `false` being undefined.

```sh
bin/composer-require-checker check --config-file=path/to/config.json /path/to/your/project/composer.json
``` 

### Scan Additional Files

To scan files, that are not part of your autoload definition you may add glob patterns to the config file's `scan-files`
section. Copy the default file and add to your copy.

The following example would also scan the file `bin/console` and all files with `.php` extension within your `bin/` folder:

```
"scan-files" : ["bin/console", "bin/*.php"]
```

If you don't like copying the tool's default settings, consider adding these paths to the Composer autoloading section 
of your project instead.

## Usage

Composer require checker runs on an existing directory structure. It does not change your code, and does not even install your composer dependencies. That is a task that is entirely up to you, allowing you to change/improve things after a scan to see if it fixes the issue.

So the usual workflow would be

1. Clone your repo
2. `composer install` your dependencies
3. `composer-require-checker check` your code

### Dealing with custom installer plugins

Composer require checker only fetches it's knowledge of where files are from your project's `composer.json`. It does not use Composer itself to understand custom directory structures.

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
