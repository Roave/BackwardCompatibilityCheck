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
          default = {
            type = "app";
            program = lib.getExe self'.packages.backwardcompatibilitycheck;
          };
        };

        packages = {
          backwardcompatibilitycheck = php.buildComposerProject {
            pname = "backwardcompatibilitycheck";
            version = "8.5.x-dev";

            src = ./.;

            vendorHash = "sha256-QI2wm3m9LTJoeN9g9R3E0BqBQ3PeB81QGSET2LXGKdw=";

            meta.mainProgram = "roave-backward-compatibility-check";
          };

          phar = pkgs.stdenvNoCC.mkDerivation {
            pname = "backwardcompatibilitycheck-phar";
            version = "8.5.x-dev";

            src = ./.;

            buildInputs = [
              php.packages.box
              php.packages.composer
            ];

            buildPhase = ''
              runHook preBuild

              cp -ar ${self'.packages.backwardcompatibilitycheck}/share/php/backwardcompatibilitycheck/vendor .
              box compile

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
