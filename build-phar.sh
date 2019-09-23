#!/usr/bin/env bash

set -e

mkdir -p tmp

# Install humbug/box
composer --working-dir=tmp require humbug/box "^3.8" --no-interaction --no-progress --no-suggest

# Remove dev dependencies for package distribution
composer install --no-dev

tmp/vendor/bin/box compile

composer install

rm -rf tmp
