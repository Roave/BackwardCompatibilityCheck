<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Factory;

use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Exception\InvalidDirectory;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileInfo;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

/**
 * @deprecated this class builds a simplistic reflector with a DirectoriesSourceLocator around a given
 *             path: that is no longer the preferred approach.
 *             Please use {@see \Roave\BackwardCompatibilityCheck\Factory\ComposerInstallationReflectorFactory} instead.
 *
 * @codeCoverageIgnore
 */
final class DirectoryReflectorFactory
{
    private Locator $astLocator;

    public function __construct(Locator $astLocator)
    {
        $this->astLocator = $astLocator;
    }

    /**
     * @throws InvalidFileInfo
     * @throws InvalidDirectory
     */
    public function __invoke(
        string $directory,
        SourceLocator $dependencies
    ): ClassReflector {
        return new ClassReflector(
            new MemoizingSourceLocator(new AggregateSourceLocator([
                new DirectoriesSourceLocator([$directory], $this->astLocator),
                $dependencies,
            ]))
        );
    }
}
