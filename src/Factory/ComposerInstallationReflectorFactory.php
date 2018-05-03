<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Factory;

use Roave\BackwardCompatibility\LocateSources\LocateSources;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Exception\InvalidDirectory;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileInfo;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

final class ComposerInstallationReflectorFactory
{
    private LocateSources $locateSources;

    public function __construct(LocateSources $locateSources)
    {
        $this->locateSources = $locateSources;
    }

    /**
     * @throws InvalidFileInfo
     * @throws InvalidDirectory
     */
    public function __invoke(
        string $installationDirectory,
        SourceLocator $dependencies
    ): ClassReflector {
        return new ClassReflector(
            new MemoizingSourceLocator(new AggregateSourceLocator([
                ($this->locateSources)($installationDirectory),
                $dependencies,
            ]))
        );
    }
}
