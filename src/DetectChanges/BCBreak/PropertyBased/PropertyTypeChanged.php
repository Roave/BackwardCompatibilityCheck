<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsContravariant;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsCovariant;
use Roave\BackwardCompatibility\Formatter\ReflectionPropertyName;
use Roave\BetterReflection\Reflection\ReflectionProperty;

/**
 * Type declarations for properties are invariant: you can't restrict the type because the consumer may
 * write invalid values to it, and you cannot widen the type because the consumer may expect a specific
 * type when reading.
 *
 * Documented types are too advanced for this library to inspect, at the moment, since `vimeo/psalm`,
 * `phpstan/phpstan` and PSR-5 are constantly evolving.
 *
 * For now, this library will limit itself at inspecting reflection-based type definitions, until better
 * utilities for parsing docblocks (maintained elsewhere) are available and integrated here.
 */
final class PropertyTypeChanged implements PropertyBased
{
    private ReflectionPropertyName $formatProperty;

    public function __construct(
        private TypeIsContravariant $typeIsContravariant,
        private TypeIsCovariant $typeIsCovariant
    ) {
        $this->formatProperty = new ReflectionPropertyName();
    }

    public function __invoke(ReflectionProperty $fromProperty, ReflectionProperty $toProperty): Changes
    {
        $fromType = $fromProperty->getType();
        $toType   = $toProperty->getType();

        if (($this->typeIsCovariant)($fromType, $toType) && ($this->typeIsContravariant)($fromType, $toType)) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'Type type of property %s changed from %s to %s',
                ($this->formatProperty)($fromProperty),
                $fromType?->__toString() ?? 'having no type',
                $toType?->__toString() ?? 'having no type'
            )
        ));
    }
}
