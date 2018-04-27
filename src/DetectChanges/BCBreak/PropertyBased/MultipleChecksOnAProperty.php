<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use function array_reduce;

final class MultipleChecksOnAProperty implements PropertyBased
{
    /** @var PropertyBased[] */
    private $checks;

    public function __construct(PropertyBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function __invoke(ReflectionProperty $fromProperty, ReflectionProperty $toProperty) : Changes
    {
        return array_reduce(
            $this->checks,
            function (Changes $changes, PropertyBased $check) use ($fromProperty, $toProperty) : Changes {
                return $changes->mergeWith($check->__invoke($fromProperty, $toProperty));
            },
            Changes::empty()
        );
    }
}
