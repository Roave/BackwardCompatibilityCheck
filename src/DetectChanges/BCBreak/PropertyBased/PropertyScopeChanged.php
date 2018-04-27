<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\PropertyBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use function sprintf;

/**
 * A property that changes from instance to static or the opposite has to be accessed differently,
 * so any of such changes are to be considered BC breaks
 */
final class PropertyScopeChanged implements PropertyBased
{
    public function __invoke(ReflectionProperty $fromProperty, ReflectionProperty $toProperty) : Changes
    {
        $fromScope = $this->scopeAsString($fromProperty);
        $toScope   = $this->scopeAsString($toProperty);

        if ($fromScope === $toScope) {
            return Changes::empty();
        }

        return Changes::fromArray([
            Change::changed(
                sprintf(
                    'Property $%s of %s changed scope from %s to %s',
                    $fromProperty->getName(),
                    $fromProperty->getDeclaringClass()->getName(),
                    $fromScope,
                    $toScope
                ),
                true
            ),
        ]);
    }

    private function scopeAsString(ReflectionProperty $property) : string
    {
        return $property->isStatic() ? 'static' : 'instance';
    }
}
