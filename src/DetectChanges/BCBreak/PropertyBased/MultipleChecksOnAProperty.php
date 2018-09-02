<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionProperty;

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
        return Changes::fromIterator((function () use ($fromProperty, $toProperty) {
            foreach ($this->checks as $check) {
                yield from $check->__invoke($fromProperty, $toProperty);
            }
        })());
    }
}
