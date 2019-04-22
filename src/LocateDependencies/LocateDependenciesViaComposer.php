<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\LocateDependencies;

use Assert\Assert;
use Composer\Installer;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\MakeLocatorForInstalledJson;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use function assert;
use function Safe\chdir;
use function Safe\getcwd;

final class LocateDependenciesViaComposer implements LocateDependencies
{
    /** @var Locator */
    private $astLocator;

    /** @var callable */
    private $makeComposerInstaller;

    public function __construct(
        callable $makeComposerInstaller,
        Locator $astLocator
    ) {
        // This is needed because the CWD of composer cannot be changed at runtime, but only at startup
        $this->makeComposerInstaller = $makeComposerInstaller;
        $this->astLocator            = $astLocator;
    }

    public function __invoke(string $installationPath) : SourceLocator
    {
        Assert::that($installationPath)->directory();
        Assert::that($installationPath . '/composer.json')->file();

        $this->runInDirectory(function () use ($installationPath) : void {
            $installer = ($this->makeComposerInstaller)($installationPath);

            Assert::that($installer)->isInstanceOf(Installer::class);
            assert($installer instanceof Installer);

            // Some defaults needed for this specific implementation:
            $installer->setDevMode(false);
            $installer->setDumpAutoloader(false);
            $installer->setRunScripts(false);
            $installer->setIgnorePlatformRequirements(true);

            $installer->run();
        }, $installationPath);

        return new AggregateSourceLocator([
            (new MakeLocatorForInstalledJson())->__invoke($installationPath, $this->astLocator),
            new PhpInternalSourceLocator($this->astLocator),
        ]);
    }

    private function runInDirectory(callable $callable, string $directoryOfExecution) : void
    {
        $originalDirectory = getcwd();

        Assert::that($originalDirectory)->string();

        try {
            chdir($directoryOfExecution);
            $callable();
        } finally {
            chdir($originalDirectory);
        }
    }
}
