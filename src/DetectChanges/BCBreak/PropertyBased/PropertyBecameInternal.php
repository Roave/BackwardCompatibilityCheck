<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use Psl\Regex;
use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\ReflectionPropertyName;
use Roave\BetterReflection\Reflection\ReflectionProperty;

/**
 * A property that is marked internal is no available to downstream consumers.
 */
final class PropertyBecameInternal implements PropertyBased
{
    private ReflectionPropertyName $formatProperty;

    public function __construct()
    {
        $this->formatProperty = new ReflectionPropertyName();
    }

    public function __invoke(ReflectionProperty $fromProperty, ReflectionProperty $toProperty): Changes
    {
        if (
            $this->isInternalDocComment($toProperty->getDocComment())
            && ! $this->isInternalDocComment($fromProperty->getDocComment())
        ) {
            return Changes::fromList(Change::changed(
                Str\format(
                    'Property %s was marked "@internal"',
                    ($this->formatProperty)($fromProperty),
                ),
                true
            ));
        }

        return Changes::empty();
    }

    private function isInternalDocComment(string $comment): bool
    {
        return Regex\matches($comment, '/\s+@internal\s+/');
    }
}
