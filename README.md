# ComposerRequireChecker

A CLI tool to analyze composer dependencies and verify that no unknown symbols are used in the sources of a package.
This will prevent you from using "soft" dependencies that are not defined within your `composer.json` require section.

[![PHP ~7.0](https://img.shields.io/badge/PHP-~7.0-brightgreen.svg?style=flat-square)](https://php.net)
[![current version](https://img.shields.io/packagist/v/maglnet/composer-require-checker.svg?style=flat-square)](https://packagist.org/packages/maglnet/composer-require-checker)
[![Build Status](https://img.shields.io/travis/maglnet/ComposerRequireChecker.svg?style=flat-square)](https://travis-ci.org/maglnet/ComposerRequireChecker)
[![Dependency Status](https://www.versioneye.com/user/projects/565df3b9b6f5ff00380001ea/badge.svg?style=flat)](https://www.versioneye.com/user/projects/565df3b9b6f5ff00380001ea)

## Usage

Composer require checker is not supposed to be installed as part of your project dependencies.  
Please check the [releases](https://github.com/maglnet/ComposerRequireChecker/releases) for available phar files or
install it in a separate directory via:

```sh
composer create-project -s dev maglnet/composer-require-checker
```

You can then use it against any of the projects on your machine:

```sh
bin/composer-require-checker check /path/to/your/project/composer.json
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
