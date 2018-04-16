<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use function sprintf;

final class PropertyVisibilityReduced implements PropertyBased
{
    private const VISIBILITY_PRIVATE = 'private';

    private const VISIBILITY_PROTECTED = 'protected';

    private const VISIBILITY_PUBLIC = 'public';

    public function compare(ReflectionProperty $fromProperty, ReflectionProperty $toProperty) : Changes
    {
        $visibilitiesFrom = $this->propertyVisibility($fromProperty);
        $visibilitiesTo   = $this->propertyVisibility($toProperty);

        if ($visibilitiesFrom <= $visibilitiesTo) {
            return Changes::new();
        }

        return Changes::fromArray([Change::changed(
            sprintf(
                'Property %s#$%s visibility reduced from %s to %s',
                $fromProperty->getDeclaringClass()->getName(),
                $fromProperty->getName(),
                $visibilitiesFrom,
                $visibilitiesTo
            ),
            true
        )]);
    }

    private function propertyVisibility(ReflectionProperty $property) : string
    {
        if ($property->isPublic()) {
            return self::VISIBILITY_PUBLIC;
        }

        if ($property->isProtected()) {
            return self::VISIBILITY_PROTECTED;
        }

        return self::VISIBILITY_PRIVATE;
    }
}
