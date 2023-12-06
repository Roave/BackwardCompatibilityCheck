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
          default = {
            type = "app";
            program = lib.getExe self'.packages.backwardcompatibilitycheck;
          };

          build-phar = {
            type = "app";
            program = lib.getExe self'.packages.build-phar-script;
          };
        };

        checks = {
          inherit (self'.packages) phar;
        };

        packages = {
          backwardcompatibilitycheck = php.buildComposerProject {
            pname = "backwardcompatibilitycheck";
            version = "8.x.x-dev";

            src = ./.;

            # This only changes when `composer.lock` is updated
            vendorHash = "sha256-LsrGmver7RyiI0/l2j6dZaqhFQf2OFyUOZb8xzFFEIA=";

            meta.mainProgram = "roave-backward-compatibility-check";
          };

          phar = pkgs.stdenvNoCC.mkDerivation {
            pname = "backwardcompatibilitycheck-phar";
            version = self'.packages.backwardcompatibilitycheck.version;

            src = self'.packages.backwardcompatibilitycheck.src;

            buildInputs = [
              php.packages.box
              php.packages.composer
            ];

            buildPhase = ''
              runHook preBuild

              cp -ar ${self'.packages.backwardcompatibilitycheck}/share/php/backwardcompatibilitycheck/vendor .
              box compile --no-interaction --quiet

              runHook postBuild
            '';

            installPhase = ''
              runHook preInstall

              mkdir -p $out
              cp dist/*.phar $out/

              runHook postInstall
            '';
          };

          build-phar-script = pkgs.writeShellApplication {
            name = "build-phar-script";

            runtimeInputs = [
              php
              php.packages.box
              php.packages.composer
            ];

            text = ''
              composer install --no-dev --quiet
              box compile --no-interaction --quiet
            '';
          };
        };
      };
  };
}
