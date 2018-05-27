<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\SourceLocator;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\AbstractSourceLocator;
use function array_slice;
use function count;
use function explode;
use function implode;
use function sprintf;

final class StubClassSourceLocator extends AbstractSourceLocator
{
    /**
     * {@inheritDoc}
     */
    protected function createLocatedSource(Identifier $identifier) : ?LocatedSource
    {
        if (! $identifier->isClass()) {
            return null;
        }

        if ($identifier->getName() === Identifier::WILDCARD) {
            return null;
        }

        $fqcn           = $identifier->getName();
        $classNameParts = explode('\\', $fqcn);
        $shortName      = array_slice($classNameParts, -1)[0];
        $namespaceName  = implode('\\', array_slice($classNameParts, 0, count($classNameParts) - 1));

        return new LocatedSource(
            sprintf('<?php namespace %s{interface %s {}}', $namespaceName, $shortName),
            null
        );
    }
}
