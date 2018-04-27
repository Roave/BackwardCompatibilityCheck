<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\TraitBased;

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
    public function __invoke(ReflectionClass $fromTrait, ReflectionClass $toTrait) : Changes
    {
        if ($this->isClass($fromTrait) || ! $this->isClass($toTrait)) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            sprintf('Trait %s became a class', $fromTrait->getName()),
            true
        ));
    }

    /**
     * According to the current state of the PHP ecosystem, we only have traits, interfaces and classes
     */
    private function isClass(ReflectionClass $class) : bool
    {
        return ! ($class->isTrait() || $class->isInterface());
    }
}
