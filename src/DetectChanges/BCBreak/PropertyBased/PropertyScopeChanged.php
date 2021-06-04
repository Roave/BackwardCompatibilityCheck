<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionProperty;

/**
 * A property that changes from instance to static or the opposite has to be accessed differently,
 * so any of such changes are to be considered BC breaks
 */
final class PropertyScopeChanged implements PropertyBased
{
    public function __invoke(ReflectionProperty $fromProperty, ReflectionProperty $toProperty): Changes
    {
        $fromScope = $this->scopeAsString($fromProperty);
        $toScope   = $this->scopeAsString($toProperty);

        if ($fromScope === $toScope) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'Property $%s of %s changed scope from %s to %s',
                $fromProperty->getName(),
                $fromProperty->getDeclaringClass()->getName(),
                $fromScope,
                $toScope
            ),
            true
        ));
    }

    private function scopeAsString(ReflectionProperty $property): string
    {
        return $property->isStatic() ? 'static' : 'instance';
    }
}
