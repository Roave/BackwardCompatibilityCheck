# API Compare Tool

A tool that can be used to compare two versions of a class API in PHP
code.

## Pre-requisites/assumptions

 * Your project uses `git`
 * You use semver formatted `git` tags like `1.2.3` to mark releases

## Installation

You must currently install the tool using:

```bash
$ composer require roave/api-compare
```

## Usage

### Adding to CI pipeline

The typical intended usage is to just add an execution of `api-compare`
into your CI build, by running:

```bash
$ vendor/bin/api-compare
```

This will automatically detect the last minor version tagged, and
compare the API against the current `HEAD`. If any BC breaks are found,
the tool returns a non-zero status, which on most CI systems will cause
the build to fail.

### Running manually

You can also run the tool by hand, for example to generate additional
documentation for changelogs:

```bash
$ vendor/bin/api-compare --markdown=bc-breaks.md
```

### CLI options

Running the tool with options:

```bash
$ vendor/bin/api-compare [--from=] [--to=] [--markdown=] [<sources-path>]
```

 * `--from=<revision>` specify manually what the "old" version is (e.g.
   `1.0.0` or a specific `git` hash). If not provided, the tool will
   attempt to figure out the last minor version released.
 * `--to=<revision>` specify manually what the "new" version (e.g.
   `1.1.0` or a specific `git` hash). The default value is `HEAD`.
 * `--markdown=<filename>` If provided, the tool will generate the list
   of changes in markdown format.
 * `<sources-path>` if given, you can specify in which directory to
   examine for classes. This defaults to `src`.

## Configuration

There are currently no configuration options available.
