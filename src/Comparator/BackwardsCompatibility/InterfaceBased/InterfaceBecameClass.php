<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\InterfaceBased;

use Assert\Assert;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

/**
 * An interface cannot become abstract without introducing an explicit BC break, since
 * all implementors need to be changed to implement it instead of extending it.
 */
final class InterfaceBecameClass implements InterfaceBased
{
    public function compare(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        Assert::that($fromClass->getName())->same($toClass->getName());

        if ($toClass->isInterface() || ! $fromClass->isInterface()) {
            // checking whether a class became an interface is done in `ClassBecameInterface`
            return Changes::new();
        }

        return Changes::fromArray([Change::changed(
            sprintf('Interface %s became a class', $fromClass->getName()),
            true
        )]);
    }
}
