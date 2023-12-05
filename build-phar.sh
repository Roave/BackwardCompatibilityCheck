#!/usr/bin/env nix-shell
#! nix-shell -i bash --pure
#! nix-shell -p bash phpPackages.box phpPackages.composer
#! nix-shell -I nixpkgs=https://github.com/NixOS/nixpkgs/archive/fae7198d73783051aae436f84bb91bd9932a6f6d.tar.gz
set -e

BOX_DIR="/tmp/box"

mkdir -p ${BOX_DIR}

# Remove dev dependencies for package distribution
composer install --no-dev

box compile

composer install

rm -rf ${BOX_DIR}
