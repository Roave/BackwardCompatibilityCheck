<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Psl\Json;
use Psl\Str;
use Psl\Dict;
use Psl\Vec;

/**
 * A class ancestor (interface or class) cannot be removed, as that breaks type
 * checking in consumers.
 */
final class AncestorRemoved implements ClassBased
{
    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass): Changes
    {
        $removedAncestors = Vec\concat(
            Vec\values(Dict\diff($fromClass->getParentClassNames(), $toClass->getParentClassNames())),
            Vec\values(Dict\diff($fromClass->getInterfaceNames(), $toClass->getInterfaceNames()))
        );

        if (! $removedAncestors) {
            return Changes::empty();
        }

        return Changes::fromList(Change::removed(
            Str\format(
                'These ancestors of %s have been removed: %s',
                $fromClass->getName(),
                Json\encode($removedAncestors)
            ),
            true
        ));
    }
}
