<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\InterfaceBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function sprintf;

/**
 * An interface cannot become concrete without introducing an explicit BC break, since
 * all implementors need to be changed to implement it instead of extending it.
 */
final class InterfaceBecameClass implements InterfaceBased
{
    public function compare(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        if (! $this->isClass($toClass) || ! $fromClass->isInterface()) {
            // checking whether a class became an interface is done in `ClassBecameInterface`
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
