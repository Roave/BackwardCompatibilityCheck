<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionProperty;

final class AccessiblePropertyChanged implements PropertyBased
{
    private PropertyBased $propertyBased;

    public function __construct(PropertyBased $propertyBased)
    {
        $this->propertyBased = $propertyBased;
    }

    public function __invoke(ReflectionProperty $fromProperty, ReflectionProperty $toProperty): Changes
    {
        if ($fromProperty->isPrivate()) {
            return Changes::empty();
        }

        return ($this->propertyBased)($fromProperty, $toProperty);
    }
}
