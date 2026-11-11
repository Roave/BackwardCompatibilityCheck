<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\ReflectionPropertyName;
use Roave\BetterReflection\Reflection\ReflectionProperty;

/**
 * A property whose write ("set") visibility is reduced through asymmetric
 * visibility (`public private(set)`, `public protected(set)`, ...) can no
 * longer be written from scopes that could previously write to it.
 */
final class PropertySetVisibilityReduced implements PropertyBased
{
    private ReflectionPropertyName $formatProperty;

    public function __construct()
    {
        $this->formatProperty = new ReflectionPropertyName();
    }

    private const string VISIBILITY_PRIVATE = 'private';

    private const string VISIBILITY_PROTECTED = 'protected';

    private const string VISIBILITY_PUBLIC = 'public';

    public function __invoke(ReflectionProperty $fromProperty, ReflectionProperty $toProperty): Changes
    {
        // A symmetric visibility reduction is reported by PropertyVisibilityReduced
        if (! $toProperty->isPrivateSet() && ! $toProperty->isProtectedSet()) {
            return Changes::empty();
        }

        $visibilityFrom = $this->propertySetVisibility($fromProperty);
        $visibilityTo   = $this->propertySetVisibility($toProperty);

        // A `final` class has no child classes, so `protected(set)` grants no more than `private(set)` there
        if ($visibilityFrom === self::VISIBILITY_PROTECTED && $fromProperty->getDeclaringClass()->isFinal()) {
            $visibilityFrom = self::VISIBILITY_PRIVATE;
        }

        // Works because private, protected and public are sortable:
        if ($visibilityFrom <= $visibilityTo) {
            return Changes::empty();
        }

        // A property becoming `readonly` is implicitly `protected(set)`, and is reported by PropertyBecameReadonly
        if (
            $visibilityTo === self::VISIBILITY_PROTECTED
            && $toProperty->isReadOnly()
            && ! $fromProperty->isReadOnly()
        ) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'Property %s set visibility reduced from %s to %s',
                ($this->formatProperty)($fromProperty),
                $visibilityFrom,
                $visibilityTo,
            ),
        ));
    }

    private function propertySetVisibility(ReflectionProperty $property): string
    {
        if ($property->isPrivateSet()) {
            return self::VISIBILITY_PRIVATE;
        }

        if ($property->isProtectedSet()) {
            return self::VISIBILITY_PROTECTED;
        }

        if ($property->isPublic()) {
            return self::VISIBILITY_PUBLIC;
        }

        if ($property->isProtected()) {
            return self::VISIBILITY_PROTECTED;
        }

        return self::VISIBILITY_PRIVATE;
    }
}
