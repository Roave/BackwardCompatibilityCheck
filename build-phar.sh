#!/usr/bin/env bash

if [ ! -f box.phar ]; then
    wget https://github.com/humbug/box/releases/download/3.0.0-beta.4/box.phar -O box.phar
fi

# lock PHP to minimum allowed version
composer config platform.php 7.2.0
composer update --no-dev

php box.phar compile

git checkout composer.*
composer install
