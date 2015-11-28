# ComposerRequireChecker

A CLI tool to analyze composer dependencies and verify that no unknown symbols are used in the sources of a package.
This will prevent you from using "soft" dependencies that are not defined within your `composer.json` require section.

[![Build Status](https://travis-ci.org/maglnet/ComposerRequireChecker.svg?branch=master)](https://travis-ci.org/maglnet/ComposerRequireChecker)
