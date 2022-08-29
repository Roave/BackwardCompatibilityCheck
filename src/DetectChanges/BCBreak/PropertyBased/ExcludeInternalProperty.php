<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use Psl\Regex;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionProperty;

final class ExcludeInternalProperty implements PropertyBased
{
    public function __construct(private PropertyBased $propertyBased)
    {
    }

    public function __invoke(ReflectionProperty $fromProperty, ReflectionProperty $toProperty): Changes
    {
        if ($this->isInternalDocComment($fromProperty->getDocComment())) {
            return Changes::empty();
        }

        return ($this->propertyBased)($fromProperty, $toProperty);
    }

    private function isInternalDocComment(string $comment): bool
    {
        return Regex\matches($comment, '/\s+@internal\s+/');
    }
}
