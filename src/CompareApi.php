<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility;

use Roave\BetterReflection\Reflector\ClassReflector;

interface CompareApi
{
    /**
     * @param ClassReflector $definedSymbols              containing only defined symbols we want to check
     * @param ClassReflector $pastSourcesWithDependencies capable of giving us symbols with their dependencies from the
     *                                                    old version of the sources
     * @param ClassReflector $newSourcesWithDependencies  capable of giving us symbols with their dependencies from the
     *                                                    new version of the sources
     */
    public function __invoke(
        ClassReflector $definedSymbols,
        ClassReflector $pastSourcesWithDependencies,
        ClassReflector $newSourcesWithDependencies
    ) : Changes;
}
