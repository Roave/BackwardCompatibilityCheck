{
  description = "PHP development environments";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
    nix-shell.url = "github:loophp/nix-shell";
    systems.url = "github:nix-systems/default";
    # This should stay until Box 4.5.1 is not on `nixos-unstable` branch
    nixpkgs-master.url = "github:NixOS/nixpkgs/master";
  };

  outputs = inputs@{ self, flake-parts, systems, ... }: flake-parts.lib.mkFlake { inherit inputs; } {
    systems = import systems;

    perSystem = { config, self', inputs', pkgs, system, lib, ... }:
      let
        php = pkgs.api.buildPhpFromComposer {
          src = ./.;
          php = pkgs.box451.php81; # Change to php56, php70, ..., php81, php82, php83 etc.
        };
      in
      {
        _module.args.pkgs = import self.inputs.nixpkgs {
          inherit system;
          overlays = [
            inputs.nix-shell.overlays.default
            (final: prev: {
              box451 = import inputs.nixpkgs-master {
                inherit system;
              };
            })
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
              php
              php.packages.box
              php.packages.composer
              pkgs.git
            ];

            text = ''
              composer install --no-dev --quiet

              box compile
            '';
          };
        };
      };
  };
}
