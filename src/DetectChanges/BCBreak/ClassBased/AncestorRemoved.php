<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function array_diff;
use function array_merge;
use function json_encode;
use function sprintf;

/**
 * A class ancestor (interface or class) cannot be removed, as that breaks type
 * checking in consumers.
 */
final class AncestorRemoved implements ClassBased
{
    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        $removedAncestors = array_merge(
            array_diff($fromClass->getParentClassNames(), $toClass->getParentClassNames()),
            array_diff($fromClass->getInterfaceNames(), $toClass->getInterfaceNames())
        );

        if (! $removedAncestors) {
            return Changes::empty();
        }

        return Changes::fromList(Change::removed(
            sprintf(
                'These ancestors of %s have been removed: %s',
                $fromClass->getName(),
                json_encode($removedAncestors)
            ),
            true
        ));
    }
}
