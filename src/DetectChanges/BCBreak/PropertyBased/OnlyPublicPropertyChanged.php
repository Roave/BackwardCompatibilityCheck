<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionProperty;

final class OnlyPublicPropertyChanged implements PropertyBased
{
    /** @var PropertyBased */
    private $propertyBased;

    public function __construct(PropertyBased $propertyBased)
    {
        $this->propertyBased = $propertyBased;
    }

    public function __invoke(ReflectionProperty $fromProperty, ReflectionProperty $toProperty) : Changes
    {
        if (! $fromProperty->isPublic()) {
            return Changes::empty();
        }

        return $this->propertyBased->__invoke($fromProperty, $toProperty);
    }
}
