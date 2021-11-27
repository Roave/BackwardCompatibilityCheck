<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use Psl\Str;
use Psl\Vec;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\ReflectionPropertyName;
use Roave\BetterReflection\Reflection\ReflectionProperty;

use function array_merge;

/**
 * Type declarations for properties are invariant: you can't restrict the type because the consumer may
 * write invalid values to it, and you cannot widen the type because the consumer may expect a specific
 * type when reading.
 */
final class PropertyDocumentedTypeChanged implements PropertyBased
{
    private ReflectionPropertyName $formatProperty;

    public function __construct()
    {
        $this->formatProperty = new ReflectionPropertyName();
    }

    public function __invoke(ReflectionProperty $fromProperty, ReflectionProperty $toProperty): Changes
    {
        $toNativeType = [];
        $toType       = $toProperty->getType();
        if ($toType !== null) {
            $toNativeType[] = $toType->getName();
        }

        if ($fromProperty->getDocComment() === '') {
            return Changes::empty();
        }

        $fromTypes = Vec\sort($fromProperty->getDocBlockTypeStrings());
        $toTypes   = Vec\sort(array_merge($toNativeType, $toProperty->getDocBlockTypeStrings()));

        if ($fromTypes === $toTypes) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'Type documentation for property %s changed from %s to %s',
                ($this->formatProperty)($fromProperty),
                Str\join($fromTypes, '|') ?: 'having no type',
                Str\join($toTypes, '|') ?: 'having no type'
            ),
            true
        ));
    }
}
