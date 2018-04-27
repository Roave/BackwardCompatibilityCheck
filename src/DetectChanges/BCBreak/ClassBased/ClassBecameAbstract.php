<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\ClassBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function sprintf;

/**
 * A class cannot become abstract without introducing an explicit BC break, since
 * all child classes or implementors need to be changed to implement its abstract API,
 * and all instantiations start to fail.
 */
final class ClassBecameAbstract implements ClassBased
{
    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        if ($fromClass->isInterface() !== $toClass->isInterface()) {
            // checking whether a class became an interface is done in `ClassBecameInterface`
            return Changes::empty();
        }

        if ($fromClass->isAbstract() || ! $toClass->isAbstract()) {
            return Changes::empty();
        }

        return Changes::fromArray([Change::changed(
            sprintf('Class %s became abstract', $fromClass->getName()),
            true
        ),
        ]);
    }
}
