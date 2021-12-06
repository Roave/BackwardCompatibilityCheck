<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility;

use Roave\BetterReflection\Reflector\Reflector;

interface CompareApi
{
    /**
     * @param Reflector $definedSymbols              containing only defined symbols we want to check
     * @param Reflector $pastSourcesWithDependencies capable of giving us symbols with their dependencies from the
     *                                                    old version of the sources
     * @param Reflector $newSourcesWithDependencies  capable of giving us symbols with their dependencies from the
     *                                                    new version of the sources
     */
    public function __invoke(
        Reflector $definedSymbols,
        Reflector $pastSourcesWithDependencies,
        Reflector $newSourcesWithDependencies
    ): Changes;
}
