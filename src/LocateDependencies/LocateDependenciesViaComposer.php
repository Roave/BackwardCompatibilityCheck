<?php

declare(strict_types=1);

namespace Roave\ApiCompare\LocateDependencies;

use Assert\Assert;
use Composer\Installer;
use Roave\ApiCompare\SourceLocator\StaticClassMapSourceLocator;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

final class LocateDependenciesViaComposer implements LocateDependencies
{
    /** @var Installer */
    private $installer;

    /** @var Locator */
    private $astLocator;

    public function __construct(Installer $installer, Locator $astLocator)
    {
        $this->installer  = $installer;
        $this->astLocator = $astLocator;

        // Some defaults needed for this specific implementation:
        $this->installer->setDevMode(false);
        $this->installer->setDumpAutoloader(true);
        $this->installer->setRunScripts(false);
        $this->installer->setOptimizeAutoloader(true);
        $this->installer->setClassMapAuthoritative(true);
        $this->installer->setIgnorePlatformRequirements(true);
    }

    public function __invoke(string $installationPath) : SourceLocator
    {
        Assert::that($installationPath)->directory();
        Assert::that($installationPath . '/composer.json')->file();

        $this->runInDirectory(function () {
            $this->installer->run();
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
        ]);
    }

    private function sourceLocatorFromAutoloadStatic(ReflectionClass $autoloadStatic) : SourceLocator
    {
        $classMapProperty = $autoloadStatic->getProperty('classMap');

        Assert::that($classMapProperty)->notNull();

        /** @var ReflectionProperty $classMapProperty */
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

        Assert::that($filesMapProperty)->notNull();

        /** @var ReflectionProperty $filesMapProperty */
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
