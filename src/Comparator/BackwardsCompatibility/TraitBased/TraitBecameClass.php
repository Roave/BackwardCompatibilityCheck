<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\TraitBased;

use Assert\Assert;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function sprintf;

/**
 * A trait cannot change to become a class, as that forces all implementations
 * that use it to change from `use` to inheritance (if even possible)
 */
final class TraitBecameClass implements TraitBased
{
    public function compare(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        Assert::that($fromClass->getName())->same($toClass->getName());

        if ($this->isClass($fromClass) || ! $this->isClass($toClass)) {
            return Changes::new();
        }

        return Changes::fromArray([Change::changed(
            sprintf('Interface %s became a class', $fromClass->getName()),
            true
        ),
        ]);
    }

    /**
     * According to the current state of the PHP ecosystem, we only have traits, interfaces and classes
     */
    private function isClass(ReflectionClass $class) : bool
    {
        return ! ($class->isTrait() || $class->isInterface());
    }
}
