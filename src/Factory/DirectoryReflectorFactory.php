<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Factory;

use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Exception\InvalidDirectory;
use Roave\BetterReflection\SourceLocator\Exception\InvalidFileInfo;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

/**
 * @codeCoverageIgnore
 */
final class DirectoryReflectorFactory
{
    /** @var Locator */
    private $astLocator;

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
    ) : ClassReflector {
        return new ClassReflector(
            new AggregateSourceLocator([
                new DirectoriesSourceLocator([$directory], $this->astLocator),
                $dependencies
            ])
        );
    }
}
