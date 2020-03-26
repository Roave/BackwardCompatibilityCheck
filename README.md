# Roave Backward Compatibility Check

[![Build Status](https://travis-ci.org/Roave/BackwardCompatibilityCheck.svg?branch=master)](https://travis-ci.org/Roave/BackwardCompatibilityCheck)  [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Roave/BackwardCompatibilityCheck/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Roave/BackwardCompatibilityCheck/?branch=master) [![Latest Stable Version](https://poser.pugx.org/roave/backward-compatibility-check/v/stable)](https://packagist.org/packages/roave/backward-compatibility-check) [![License](https://poser.pugx.org/roave/backward-compatibility-check/license)](https://packagist.org/packages/roave/backward-compatibility-check)

A tool that can be used to verify BC breaks between two versions
of a PHP library.

## Pre-requisites/assumptions

 * Your project uses `git`
 * Your project uses `composer.json` to define its dependencies
 * All source paths are covered by an `"autoload"` section in `composer.json`

## Installation

```bash
composer require --dev roave/backward-compatibility-check
```

### Install with Docker

You can also use Docker to run `roave-backward-compatibility-check`: 

```bash
docker run --rm -v `pwd`:/app nyholm/roave-bc-check
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

#### Github action

You can use it as a Github Action like this:

_.github/main.workflow_
```
on: [push]
name: Test
jobs:
    roave_bc_check:
        name: Roave BC Check
        runs-on: ubuntu-latest
        steps:
            - uses: actions/checkout@master
            - name: fetch tags
              run: git fetch --depth=1 origin +refs/tags/*:refs/tags/*
            - name: Roave BC Check
              uses: docker://nyholm/roave-bc-check-ga
```

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
