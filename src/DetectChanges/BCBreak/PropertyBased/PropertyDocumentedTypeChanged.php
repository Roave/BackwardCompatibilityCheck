<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\ReflectionPropertyName;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use function implode;
use function Safe\sort;
use function Safe\sprintf;

/**
 * Type declarations for properties are invariant: you can't restrict the type because the consumer may
 * write invalid values to it, and you cannot widen the type because the consumer may expect a specific
 * type when reading.
 */
final class PropertyDocumentedTypeChanged implements PropertyBased
{
    /** @var ReflectionPropertyName */
    private $formatProperty;

    public function __construct()
    {
        $this->formatProperty = new ReflectionPropertyName();
    }

    public function __invoke(ReflectionProperty $fromProperty, ReflectionProperty $toProperty) : Changes
    {
        if ($fromProperty->getDocComment() === '') {
            return Changes::empty();
        }

        $fromTypes = $fromProperty->getDocBlockTypeStrings();
        $toTypes   = $toProperty->getDocBlockTypeStrings();

        sort($fromTypes);
        sort($toTypes);

        if ($fromTypes === $toTypes) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            sprintf(
                'Type documentation for property %s changed from %s to %s',
                $this->formatProperty->__invoke($fromProperty),
                implode('|', $fromTypes) ?: 'having no type',
                implode('|', $toTypes) ?: 'having no type'
            ),
            true
        ));
    }
}
