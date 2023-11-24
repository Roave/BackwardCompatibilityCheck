# Roave Backward Compatibility Check

[![SWUbanner](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner2-direct.svg)](https://github.com/vshymanskyy/StandWithUkraine/blob/main/docs/README.md)

[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FRoave%2FBackwardCompatibilityCheck%2F7.1.x)](https://dashboard.stryker-mutator.io/reports/github.com/Roave/BackwardCompatibilityCheck/7.1.x)
[![Type Coverage](https://shepherd.dev/github/Roave/BackwardCompatibilityCheck/coverage.svg)](https://shepherd.dev/github/Roave/BackwardCompatibilityCheck)
[![Latest Stable Version](https://poser.pugx.org/roave/backward-compatibility-check/v/stable)](https://packagist.org/packages/roave/backward-compatibility-check)
[![License](https://poser.pugx.org/roave/backward-compatibility-check/license)](https://packagist.org/packages/roave/backward-compatibility-check)

A tool that can be used to verify BC breaks between two versions
of a PHP library.

## Pre-requisites/assumptions

 * Your project uses `git`
 * Your project uses `composer.json` to define its dependencies
 * All source paths are covered by an `"autoload"` section in `composer.json`
 * Changes need to be committed to `git` to be covered. You can implement your own logic to extract sources and dependencies from a project though.

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

*NOTE:* since this tool relies on tags, you need to make sure tags are fetched
as part of your CI pipeline. For example in a GitHub action, note the use of
[`fetch-depth: 0`](https://github.com/actions/checkout#fetch-all-history-for-all-tags-and-branches):

```yaml
jobs:
  roave-backwards-compatibility-check:
    name: Roave Backwards Compatibility Check
      runs-on: ubuntu-latest
      steps:
        - uses: actions/checkout@v2
          with:
            fetch-depth: 0
        - name: "Install PHP"
          uses: shivammathur/setup-php@v2
          with:
            php-version: "8.0"
        - name: "Install dependencies"
          run: "composer install"
        - name: "Check for BC breaks"
          run: "vendor/bin/roave-backward-compatibility-check"
```

#### Nyholm Github Action

Tobias Nyholm also offers [a simple GitHub action](https://github.com/Nyholm/roave-bc-check-docker)
that you can use in your Github pipeline. We recommend this for most cases as
it is simple to set up:

_.github/workflows/main.yml_
```yaml
on: [push]
name: Test
jobs:
  roave-backwards-compatibility-check:
    name: Roave Backwards Compatibility Check
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
        with:
          fetch-depth: 0
      - name: "Check for BC breaks"
        uses: docker://nyholm/roave-bc-check-ga
```

### Running manually

To generate additional documentation for changelogs:

```bash
vendor/bin/roave-backward-compatibility-check --format=markdown > results.md
```

### GitHub Actions

When running in GitHub Actions, it is endorsed to use the `--format=github-actions` output format:

```bash
vendor/bin/roave-backward-compatibility-check --format=github-actions
```

### Documentation

If you need further guidance:

```bash
vendor/bin/roave-backward-compatibility-check --help
```

## Configuration

There are currently no configuration options available.
