#!/usr/bin/env bash

set -e

BOX_DIR="/tmp/box"

mkdir -p ${BOX_DIR}

# Install humbug/box
composer --working-dir=${BOX_DIR} require humbug/box "^3.8.4" --no-interaction --no-progress --no-suggest

# Remove dev dependencies for package distribution
composer install --no-dev

${BOX_DIR}/vendor/bin/box compile

composer install

rm -rf ${BOX_DIR}
