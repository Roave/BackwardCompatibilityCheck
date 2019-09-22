#!/usr/bin/env bash

if [ ! -f box.phar ]; then
    wget https://github.com/humbug/box/releases/download/3.0.0-beta.4/box.phar -O box.phar
fi

# Remove dev dependencies for package distribution
composer install --no-dev

php box.phar compile

composer install
