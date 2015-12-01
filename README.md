# ComposerRequireChecker

A CLI tool to analyze composer dependencies and verify that no unknown symbols are used in the sources of a package.
This will prevent you from using "soft" dependencies that are not defined within your `composer.json` require section.

[![Build Status](https://travis-ci.org/maglnet/ComposerRequireChecker.svg?branch=master)](https://travis-ci.org/maglnet/ComposerRequireChecker)

## Usage

Composer require checker is not supposed to be installed as part of your project dependencies.
Instead, please install it in a separate directory via:

```sh
composer create-project -s dev maglnet/composer-require-checker
```

You can then use it against any of the projects on your machine:

```sh
bin/composer-require-checker check /path/to/your/project/composer.json
```

## License

This package is made available under the [MIT LICENSE](LICENSE).

## Credits

This package was initially designed by [Matthias Glaub](https://github.com/maglnet)
and [Marco Pivetta](https://github.com/ocramius).
