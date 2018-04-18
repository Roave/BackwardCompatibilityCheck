<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Formatter\ReflectionPropertyName;
use Roave\BetterReflection\Reflection\ReflectionProperty;

final class PropertyDefaultValueChanged implements PropertyBased
{
    /** @var ReflectionPropertyName */
    private $formatProperty;

    public function __construct()
    {
        $this->formatProperty = new ReflectionPropertyName();
    }

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
                    'Property %s changed default value from %s to %s',
                    $this->formatProperty->__invoke($fromProperty),
                    var_export($fromPropertyDefaultValue, true),
                    var_export($toPropertyDefaultValue, true)
                ),
                true
            )
        ]);
    }
}
