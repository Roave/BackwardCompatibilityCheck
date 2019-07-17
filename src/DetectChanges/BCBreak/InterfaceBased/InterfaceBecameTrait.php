<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function Safe\sprintf;

/**
 * An interface cannot become a trait without introducing an explicit BC break, since
 * all implementors need to be changed to implement it instead of extending it.
 */
final class InterfaceBecameTrait implements InterfaceBased
{
    public function __invoke(ReflectionClass $fromInterface, ReflectionClass $toInterface) : Changes
    {
        if (! $toInterface->isTrait() || ! $fromInterface->isInterface()) {
            // checking whether an interface became an class is done in `InterfaceBecameClass`
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            sprintf('Interface %s became a trait', $fromInterface->getName()),
            true
        ));
    }
}
