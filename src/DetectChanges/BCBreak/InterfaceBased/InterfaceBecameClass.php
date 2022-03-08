<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

/**
 * An interface cannot become concrete without introducing an explicit BC break, since
 * all implementors need to be changed to implement it instead of extending it.
 */
final class InterfaceBecameClass implements InterfaceBased
{
    public function __invoke(ReflectionClass $fromInterface, ReflectionClass $toInterface): Changes
    {
        if (! $this->isClass($toInterface) || ! $fromInterface->isInterface()) {
            // checking whether a class became an interface is done in `ClassBecameInterface`
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format('Interface %s became a class', $fromInterface->getName())
        ));
    }

    /**
     * According to the current state of the PHP ecosystem, we only have traits, interfaces and classes
     */
    private function isClass(ReflectionClass $class): bool
    {
        return ! ($class->isTrait() || $class->isInterface());
    }
}
