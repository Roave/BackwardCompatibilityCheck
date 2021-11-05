<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\LocateDependencies;

use Composer\Installer;
use Psl;
use Psl\Env;
use Psl\Filesystem;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\MakeLocatorForInstalledJson;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

final class LocateDependenciesViaComposer implements LocateDependencies
{
    private Locator $astLocator;

    /** @psalm-var callable () : Installer $makeComposerInstaller */
    private $makeComposerInstaller;

    /**
     * @psalm-param callable () : Installer $makeComposerInstaller
     */
    public function __construct(
        callable $makeComposerInstaller,
        Locator $astLocator
    ) {
        // This is needed because the CWD of composer cannot be changed at runtime, but only at startup
        $this->makeComposerInstaller = $makeComposerInstaller;
        $this->astLocator            = $astLocator;
    }

    public function __invoke(string $installationPath, bool $includeDevelopmentDependencies): SourceLocator
    {
        Psl\invariant(Filesystem\is_file($installationPath . '/composer.json'), 'Could not locate composer.json within installation path.');

        $this->runInDirectory(function () use ($installationPath, $includeDevelopmentDependencies): void {
            $installer = ($this->makeComposerInstaller)($installationPath);

            // Some defaults needed for this specific implementation:
            $installer->setDevMode($includeDevelopmentDependencies);
            $installer->setDumpAutoloader(false);
            /**
             * @psalm-suppress DeprecatedMethod we will keep using the deprecated API until the next major release
             *                 of composer, as we otherwise need to re-design how an {@see Installer} is constructed.
             */
            $installer->setRunScripts(false);
            $installer->setIgnorePlatformRequirements(true);

            $installer->run();
        }, $installationPath);

        return new AggregateSourceLocator([
            (new MakeLocatorForInstalledJson())->__invoke($installationPath, $this->astLocator),
            new PhpInternalSourceLocator($this->astLocator, new ReflectionSourceStubber()),
        ]);
    }

    private function runInDirectory(callable $callable, string $directoryOfExecution): void
    {
        $originalDirectory = Env\current_dir();

        try {
            Env\set_current_dir($directoryOfExecution);
            $callable();
        } finally {
            Env\set_current_dir($originalDirectory);
        }
    }
}
