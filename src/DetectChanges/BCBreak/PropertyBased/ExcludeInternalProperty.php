<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use function Safe\preg_match;

final class ExcludeInternalProperty implements PropertyBased
{
    private PropertyBased $propertyBased;

    public function __construct(PropertyBased $propertyBased)
    {
        $this->propertyBased = $propertyBased;
    }

    public function __invoke(ReflectionProperty $fromProperty, ReflectionProperty $toProperty) : Changes
    {
        if ($this->isInternalDocComment($fromProperty->getDocComment())) {
            return Changes::empty();
        }

        return $this->propertyBased->__invoke($fromProperty, $toProperty);
    }

    private function isInternalDocComment(string $comment) : bool
    {
        return preg_match('/\s+@internal\s+/', $comment) === 1;
    }
}
