<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\InterfaceBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function sprintf;

/**
 * An interface cannot become a trait without introducing an explicit BC break, since
 * all implementors need to be changed to implement it instead of extending it.
 */
final class InterfaceBecameTrait implements InterfaceBased
{
    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        if (! $toClass->isTrait() || ! $fromClass->isInterface()) {
            // checking whether an interface became an class is done in `InterfaceBecameClass`
            return Changes::empty();
        }

        return Changes::fromArray([Change::changed(
            sprintf('Interface %s became a trait', $fromClass->getName()),
            true
        ),
        ]);
    }
}
