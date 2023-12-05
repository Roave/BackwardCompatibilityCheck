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
        php = pkgs.api.buildPhpFromComposer {
          src = ./.;
          php = pkgs.php81; # Change to php56, php70, ..., php81, php82, php83 etc.
        };
      in
      {
        _module.args.pkgs = import self.inputs.nixpkgs {
          inherit system;
          overlays = [
            inputs.nix-shell.overlays.default
          ];
          config.allowUnfree = true;
        };

        devShells.default = pkgs.mkShellNoCC {
          name = "php-devshell";
          buildInputs = [
            php
            php.packages.composer
            php.packages.box
          ];
        };

        apps = {
          build-phar = {
            type = "app";
            program = lib.getExe self'.packages.build-phar-script;
          };
        };

        packages = {
          build-phar-script = pkgs.writeShellApplication {
            name = "build-phar-script";

            runtimeInputs = [
              php.packages.box
              php.packages.composer
              pkgs.git
            ];

            text = ''
              composer install --no-dev
              box compile
            '';
          };
        };
      };
  };
}