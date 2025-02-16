TYPO3.Fluid Rendering Engine
============================

[![Build Status](https://github.com/TYPO3/Fluid/actions/workflows/build.yml/badge.svg)](https://github.com/TYPO3/Fluid/actions/workflows/build.yml)

TYPO3 community template engine - composer-enabled, Flow/CMS dependency-free PSR-4 edition.

Installation
------------

1. Include as composer dependency using `composer require typo3fluid/fluid`
2. Run `composer install` to generate the vendor class autoloader
3. The classes from `TYPO3.Fluid` can now be used in your composer project

Usage Examples
--------------

The library source comes with a set of example scripts to study and play around with.
They are PHP entry scripts which render templates, their partials and layouts.

```bash
$ git clone git@github.com:TYPO3/Fluid.git
$ composer update
# Run a single example file:
$ php examples/example_format.php
# Run all example files:
$ find examples/ -maxdepth 1 -name \*.php -exec php {} \;
```

Usage Documentation
-------------------

* [The Fluid template file structure - where to place which template files](doc/FLUID_STRUCTURE.md)
* [The Fluid syntax - how the special Fluid syntax works](doc/FLUID_SYNTAX.md)
* [ViewHelpers - what these classes do in the Fluid language](doc/FLUID_VIEWHELPERS.md)

Developer Documentation
-----------------------

* [Implementing Fluid - controlling how Fluid behaves in your application](doc/FLUID_IMPLEMENTATION.md)
* [Creating ViewHelpers - special PHP classes to create custom dynamic tags](doc/FLUID_CREATING_VIEWHELPERS.md)
* [Creating ExpressionNodes - special PHP classes that extend the Fluid syntax](doc/FLUID_EXPRESSIONS.md)
* [Special difference information for developers coming from TYPO3 Flow/CMS](doc/README_TYPO3.md)

License
-------
LGPL 3.0 License. See LICENSE file.
