<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Formatter\ReflectionPropertyName;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use function sprintf;

final class PropertyVisibilityReduced implements PropertyBased
{
    /** @var ReflectionPropertyName */
    private $formatProperty;

    public function __construct()
    {
        $this->formatProperty = new ReflectionPropertyName();
    }

    private const VISIBILITY_PRIVATE = 'private';

    private const VISIBILITY_PROTECTED = 'protected';

    private const VISIBILITY_PUBLIC = 'public';

    public function compare(ReflectionProperty $fromProperty, ReflectionProperty $toProperty) : Changes
    {
        $visibilityFrom = $this->propertyVisibility($fromProperty);
        $visibilityTo   = $this->propertyVisibility($toProperty);

        // Works because private, protected and public are sortable:
        if ($visibilityFrom <= $visibilityTo) {
            return Changes::new();
        }

        return Changes::fromArray([Change::changed(
            sprintf(
                'Property %s visibility reduced from %s to %s',
                $this->formatProperty->__invoke($fromProperty),
                $visibilityFrom,
                $visibilityTo
            ),
            true
        ),
        ]);
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
