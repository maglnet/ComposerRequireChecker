# ComposerRequireChecker

A CLI tool to analyze composer dependencies and verify that no unknown symbols are used in the sources of a package.
This will prevent you from using "soft" dependencies that are not defined within your `composer.json` require section.

[![PHP ~7.0](https://img.shields.io/badge/PHP-~7.0-brightgreen.svg?style=flat-square)](https://php.net)
[![current version](https://img.shields.io/packagist/v/maglnet/composer-require-checker.svg?style=flat-square)](https://packagist.org/packages/maglnet/composer-require-checker)
[![Build Status](https://img.shields.io/travis/maglnet/ComposerRequireChecker.svg?style=flat-square)](https://travis-ci.org/maglnet/ComposerRequireChecker)
[![Dependency Status](https://www.versioneye.com/user/projects/565df3b9b6f5ff00380001ea/badge.svg?style=flat)](https://www.versioneye.com/user/projects/565df3b9b6f5ff00380001ea)

## Installation / Usage

Composer require checker is not supposed to be installed as part of your project dependencies.
  
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

### PHAR file

Please check the [releases](https://github.com/maglnet/ComposerRequireChecker/releases) for available phar files.
Download the latest release and and run it like this:
```
php composer-require-checker.phar check /path/to/your/project/composer.json
```

## Configuration

Composer require checker is configured to whitelist some symbols per default. Have a look at the
[config file example](data/config.dist.json) to see which configuration options are available.

You can now adjust this file, as needed, and tell composer-require-checker to use it for it's configuration.

```sh
bin/composer-require-checker check --config-file path/to/config.json /path/to/your/project/composer.json
``` 

## License

This package is made available under the [MIT LICENSE](LICENSE).

## Credits

This package was initially designed by [Marco Pivetta](https://github.com/ocramius) and [Matthias Glaub](https://github.com/maglnet).  
And of course all [Contributors](https://github.com/maglnet/ComposerRequireChecker/graphs/contributors).

[Composer]: https://getcomposer.org
[Composer cli - global]: https://getcomposer.org/doc/03-cli.md#global
