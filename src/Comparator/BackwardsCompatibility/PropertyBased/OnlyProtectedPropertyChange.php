<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionProperty;

final class OnlyProtectedPropertyChange implements PropertyBased
{
    /** @var PropertyBased */
    private $propertyBased;

    public function __construct(PropertyBased $propertyBased)
    {
        $this->propertyBased = $propertyBased;
    }

    public function compare(ReflectionProperty $fromProperty, ReflectionProperty $toProperty) : Changes
    {
        if (! $fromProperty->isProtected()) {
            return Changes::new();
        }

        return $this->propertyBased->compare($fromProperty, $toProperty);
    }
}
