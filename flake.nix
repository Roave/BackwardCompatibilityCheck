{
  description = "PHP development environments";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
    nix-shell.url = "github:loophp/nix-shell";
    systems.url = "github:nix-systems/default";
  };

  outputs = inputs@{ self, flake-parts, systems, ... }: flake-parts.lib.mkFlake { inherit inputs; } {
    systems = import systems;

    perSystem = { config, self', inputs', pkgs, system, lib, ... }:
      let
        # This function creates a PHP interpreter with the proper required
        # extensions by reading the composer.json and infering the extensions to
        # enable.
        php = pkgs.api.buildPhpFromComposer {
          src = ./.;
          php = pkgs.php82;
        };
      in
      {
        _module.args.pkgs = import self.inputs.nixpkgs {
          inherit system;
          overlays = [
            inputs.nix-shell.overlays.default
          ];
        };

        apps = {
          build-phar = {
            type = "app";
            program = lib.getExe self'.packages.build-phar-script;
          };
        };

        devShells.default = pkgs.mkShellNoCC {
          name = "php-devshell";
          buildInputs = [
            php
            php.packages.box
            php.packages.composer
            self'.packages.build-phar-script
          ];
        };

        packages = {
          build-phar-script = pkgs.writeShellApplication {
            name = "build-phar-script";

            runtimeInputs = [
              php
              php.packages.box
              php.packages.composer
            ];

            text = ''
              rm -rf vendor
              composer install --no-dev
              box compile --no-interaction
            '';
          };
        };
      };
  };
}
