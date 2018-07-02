<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\LocateDependencies;

use Assert\Assert;
use Composer\Installer;
use Roave\BackwardCompatibility\SourceLocator\StaticClassMapSourceLocator;
use Roave\BackwardCompatibility\SourceLocator\StubClassSourceLocator;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use function array_map;
use function array_values;
use function assert;
use function chdir;
use function getcwd;
use function realpath;
use function reset;

final class LocateDependenciesViaComposer implements LocateDependencies
{
    /** @var Locator */
    private $astLocator;

    /**
     * @psalm-var callable(string):Installer
     * @var callable
     */
    private $makeComposerInstaller;

    /**
     * @psalm-param callable(string):Installer $makeComposerInstaller
     */
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
            $installer->setDumpAutoloader(true);
            $installer->setRunScripts(false);
            $installer->setOptimizeAutoloader(true);
            $installer->setClassMapAuthoritative(true);
            $installer->setIgnorePlatformRequirements(true);

            $installer->run();
        }, $installationPath);

        $autoloadStatic = $installationPath . '/vendor/composer/autoload_static.php';

        Assert::that($autoloadStatic)->file();

        $autoloadMappingClasses = (new ClassReflector(new SingleFileSourceLocator(
            $autoloadStatic,
            $this->astLocator
        )))->getAllClasses();

        Assert::that($autoloadMappingClasses)->count(1);

        /** @var ReflectionClass $generatedAutoloadClass */
        $generatedAutoloadClass = reset($autoloadMappingClasses);

        return new AggregateSourceLocator([
            $this->sourceLocatorFromAutoloadStatic($generatedAutoloadClass),
            $this->sourceLocatorFromAutoloadFiles($generatedAutoloadClass),
            new PhpInternalSourceLocator($this->astLocator),
            new StubClassSourceLocator($this->astLocator),
        ]);
    }

    private function sourceLocatorFromAutoloadStatic(ReflectionClass $autoloadStatic) : SourceLocator
    {
        $classMapProperty = $autoloadStatic->getProperty('classMap');

        Assert::that($classMapProperty)->notNull();

        assert($classMapProperty instanceof ReflectionProperty);
        /** @var string[] $classMap */
        $classMap = $classMapProperty->getDefaultValue();

        Assert::that($classMap)
              ->isArray()
              ->all()
              ->file();

        return new StaticClassMapSourceLocator($classMap, $this->astLocator);
    }

    private function sourceLocatorFromAutoloadFiles(ReflectionClass $autoloadStatic) : SourceLocator
    {
        $filesMapProperty = $autoloadStatic->getProperty('files');

        if (! $filesMapProperty) {
            return new AggregateSourceLocator();
        }

        Assert::that($filesMapProperty)->notNull();

        assert($filesMapProperty instanceof ReflectionProperty);
        $filesMap = $filesMapProperty->getDefaultValue();

        Assert::that($filesMap)
              ->isArray()
              ->all()
              ->file();

        return new AggregateSourceLocator(array_values(array_map(
            function (string $path) : SourceLocator {
                return new SingleFileSourceLocator(
                    realpath($path),
                    $this->astLocator
                );
            },
            $filesMap
        )));
    }

    private function runInDirectory(callable $callable, string $directoryOfExecution) : void
    {
        $originalDirectory = getcwd();

        try {
            chdir($directoryOfExecution);
            $callable();
        } finally {
            chdir($originalDirectory);
        }
    }
}
