<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionProperty;

final class PropertyDefaultValueChanged implements PropertyBased
{
    public function compare(ReflectionProperty $fromProperty, ReflectionProperty $toProperty) : Changes
    {
        if ($fromProperty->isPrivate()) {
            return Changes::new();
        }

        $fromPropertyDefaultValue = $fromProperty->getDefaultValue();
        $toPropertyDefaultValue   = $toProperty->getDefaultValue();

        if ($fromPropertyDefaultValue === $toPropertyDefaultValue) {
            return Changes::new();
        }

        return Changes::fromArray([
            Change::changed(
                sprintf(
                    'Property %s::$%s changed default value from %s to %s',
                    $fromProperty->getDeclaringClass()->getName(),
                    $fromProperty->getName(),
                    var_export($fromPropertyDefaultValue, true),
                    var_export($toPropertyDefaultValue, true)
                ),
                true
            )
        ]);
    }
}
