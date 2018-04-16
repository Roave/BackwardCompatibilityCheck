<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionProperty;

final class MultiPropertyBased implements PropertyBased
{
    /** @var PropertyBased[] */
    private $checks;

    public function __construct(PropertyBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function compare(ReflectionProperty $fromProperty, ReflectionProperty $toProperty) : Changes
    {
        return array_reduce(
            $this->checks,
            function (Changes $changes, PropertyBased $check) use ($fromProperty, $toProperty) : Changes {
                return $changes->mergeWith($check->compare($fromProperty, $toProperty));
            },
            Changes::new()
        );
    }
}
