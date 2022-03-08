<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\SourceLocator;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

/** @internal */
final class ReplaceSourcePathOfLocatedSources extends Locator
{
    public function __construct(
        private Locator $next,
        private string $sourcesDirectory
    ) {
    }

    // @TODO test that all methods are covered (use reflection)
    /** {@inheritDoc} */
    public function findReflection(
        Reflector $reflector,
        LocatedSource $locatedSource,
        Identifier $identifier,
    ): Reflection {
        return $this->next->findReflection(
            $reflector,
            new LocatedSourceWithStrippedSourcesDirectory($locatedSource, $this->sourcesDirectory),
            $identifier
        );
    }

    /** {@inheritDoc} */
    public function findReflectionsOfType(
        Reflector $reflector,
        LocatedSource $locatedSource,
        IdentifierType $identifierType,
    ): array {
        return $this->next->findReflectionsOfType(
            $reflector,
            new LocatedSourceWithStrippedSourcesDirectory($locatedSource, $this->sourcesDirectory),
            $identifierType
        );
    }
}
