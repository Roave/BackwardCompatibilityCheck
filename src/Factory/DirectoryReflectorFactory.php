<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Factory;

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Exception\InvalidDirectory;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileInfo;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;

/**
 * @codeCoverageIgnore
 */
final class DirectoryReflectorFactory
{
    /**
     * @throws InvalidFileInfo
     * @throws InvalidDirectory
     */
    public function __invoke(string $directory) : ClassReflector
    {
        $astLocator = (new BetterReflection())->astLocator();
        return new ClassReflector(
            new AggregateSourceLocator([
                new PhpInternalSourceLocator($astLocator),
                new EvaledCodeSourceLocator($astLocator),
                new DirectoriesSourceLocator([$directory], $astLocator),
                new AutoloadSourceLocator(),
            ])
        );
    }
}
