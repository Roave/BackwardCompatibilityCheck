# Roave Backward Compatibility Check

[![Build Status](https://travis-ci.org/Roave/BackwardCompatibilityCheck.svg?branch=master)](https://travis-ci.org/Roave/BackwardCompatibilityCheck)  [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Roave/BackwardCompatibilityCheck/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Roave/BackwardCompatibilityCheck/?branch=master) [![Latest Stable Version](https://poser.pugx.org/roave/backward-compatibility-check/v/stable)](https://packagist.org/packages/roave/backward-compatibility-check) [![License](https://poser.pugx.org/roave/backward-compatibility-check/license)](https://packagist.org/packages/roave/backward-compatibility-check)

A tool that can be used to verify BC breaks between two versions
of a PHP library.

## Pre-requisites/assumptions

 * Your project uses `git`
 * Your project uses `composer.json` to define its dependencies

## Installation

```bash
composer require --dev roave/backward-compatibility-check
```

## Usage

### Adding to a continuous integration pipeline

The typical intended usage is to just add `roave-backward-compatibility-check`
to your CI build:

```bash
vendor/bin/roave-backward-compatibility-check
```

This will automatically detect the last minor version tagged, and
compare the API against the current `HEAD`. If any BC breaks are found,
the tool returns a non-zero status, which on most CI systems will cause
the build to fail.

*NOTE:* detecting the base version only works if you have git tags in
the SemVer-compliant `x.y.z` format, such as `1.2.3`.

### Running manually

To generate additional documentation for changelogs:

```bash
vendor/bin/roave-backward-compatibility-check --format=markdown > results.md
```

### Documentation

If you need further guidance:

```bash
vendor/bin/roave-backward-compatibility-check --help
```

## Configuration

There are currently no configuration options available.
