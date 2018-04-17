<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use Assert\Assert;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

/**
 * A class cannot become abstract without introducing an explicit BC break, since
 * all child classes or implementors (if it becomes an interface)
 */
final class ClassBecameAbstract implements ClassBased
{
    public function compare(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        Assert::that($fromClass->getName())->same($toClass->getName());

        if ($fromClass->isInterface() !== $toClass->isInterface()) {
            // checking whether a class became an interface is done in `ClassBecameInterface`
            return Changes::new();
        }

        if ($fromClass->isAbstract() || ! $toClass->isAbstract()) {
            return Changes::new();
        }

        return Changes::fromArray([Change::changed(
            sprintf('Class %s became abstract', $fromClass->getName()),
            true
        )]);
    }
}
