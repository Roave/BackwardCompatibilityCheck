<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\ReflectionPropertyName;
use Roave\BetterReflection\Reflection\ReflectionProperty;

/**
 * A property that becomes `readonly` (either directly, or by its class becoming
 * `readonly`) can no longer be modified after initialization, which breaks all
 * code writing to it.
 */
final class PropertyBecameReadonly implements PropertyBased
{
    private ReflectionPropertyName $formatProperty;

    public function __construct()
    {
        $this->formatProperty = new ReflectionPropertyName();
    }

    public function __invoke(ReflectionProperty $fromProperty, ReflectionProperty $toProperty): Changes
    {
        if ($fromProperty->isReadOnly() || ! $toProperty->isReadOnly()) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'Property %s became readonly',
                ($this->formatProperty)($fromProperty),
            ),
        ));
    }
}
