# Roave Backward Compatibility Check

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
the build to fail. The failure exit code is currently hard-coded as `1`.

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
