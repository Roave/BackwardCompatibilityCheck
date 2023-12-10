{
  description = "PHP development environments";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
    nix-shell.url = "github:loophp/nix-shell";
    systems.url = "github:nix-systems/default";
    # This should stay until Box 4.6.0 is not on `nixos-unstable` branch
    nixpkgs-master.url = "github:NixOS/nixpkgs/master";
  };

  outputs = inputs@{ self, flake-parts, systems, ... }: flake-parts.lib.mkFlake { inherit inputs; } {
    systems = import systems;

    perSystem = { config, self', inputs', pkgs, system, lib, ... }:
      let
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
            (final: prev: {
              box460 = import inputs.nixpkgs-master {
                inherit system;
              };
            })
          ];
          config.allowUnfree = true;
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

        devShells.default = pkgs.mkShellNoCC {
          name = "php-devshell";
          buildInputs = [
            php
            php.packages.composer
            pkgs.box460.php82.packages.box
            self'.packages.build-phar-script
          ];
        };

        packages = {
          backwardcompatibilitycheck = php.buildComposerProject {
            pname = "backwardcompatibilitycheck";
            version = "8.x.x-dev";

            src = ./.;

            # This only changes when `composer.lock` is updated
            vendorHash = "sha256-9VGaoPpJg06/n9fmSrNInQHitWxXStG74PxaJvulMwc=";

            meta.mainProgram = "roave-backward-compatibility-check";
          };

          build-phar-script = pkgs.writeShellApplication {
            name = "build-phar-script";

            runtimeInputs = [
              php
              pkgs.box460.php82.packages.box
              php.packages.composer
            ];

            text = ''
              composer install --no-dev --quiet
              box compile --no-interaction --quiet
            '';
          };

          phar = pkgs.stdenvNoCC.mkDerivation {
            pname = "backwardcompatibilitycheck-phar";
            version = self'.packages.backwardcompatibilitycheck.version;

            src = self'.packages.backwardcompatibilitycheck.src;

            buildInputs = [
              pkgs.box460.php82.packages.box
              php.packages.composer
            ];

            buildPhase = ''
              runHook preBuild

              cp -ar ${self'.packages.backwardcompatibilitycheck}/share/php/${self'.packages.backwardcompatibilitycheck.pname}/vendor .
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
        };
      };
  };
}
