<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\ReflectionPropertyName;
use Roave\BetterReflection\Reflection\ReflectionProperty;

final class PropertyVisibilityReduced implements PropertyBased
{
    private ReflectionPropertyName $formatProperty;

    public function __construct()
    {
        $this->formatProperty = new ReflectionPropertyName();
    }

    private const VISIBILITY_PRIVATE = 'private';

    private const VISIBILITY_PROTECTED = 'protected';

    private const VISIBILITY_PUBLIC = 'public';

    public function __invoke(ReflectionProperty $fromProperty, ReflectionProperty $toProperty): Changes
    {
        $visibilityFrom = $this->propertyVisibility($fromProperty);
        $visibilityTo   = $this->propertyVisibility($toProperty);

        // Works because private, protected and public are sortable:
        if ($visibilityFrom <= $visibilityTo) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'Property %s visibility reduced from %s to %s',
                ($this->formatProperty)($fromProperty),
                $visibilityFrom,
                $visibilityTo
            )
        ));
    }

    private function propertyVisibility(ReflectionProperty $property): string
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
