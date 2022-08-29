<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use Psl\Dict;
use Psl\Json;
use Psl\Str;
use Psl\Vec;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

/**
 * An interface ancestor cannot be removed, as that breaks type checking in consumers.
 */
final class AncestorRemoved implements InterfaceBased
{
    public function __invoke(ReflectionClass $fromInterface, ReflectionClass $toInterface): Changes
    {
        $removedAncestors = Vec\values(
            Dict\diff($fromInterface->getInterfaceNames(), $toInterface->getInterfaceNames()),
        );

        if (! $removedAncestors) {
            return Changes::empty();
        }

        return Changes::fromList(Change::removed(
            Str\format(
                'These ancestors of %s have been removed: %s',
                $fromInterface->getName(),
                Json\encode($removedAncestors),
            ),
        ));
    }
}
