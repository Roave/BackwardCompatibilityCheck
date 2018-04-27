<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\PropertyBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Formatter\ReflectionPropertyName;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use function array_unique;
use function implode;
use function sort;
use function sprintf;

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

        $fromTypes = array_unique($fromProperty->getDocBlockTypeStrings());
        $toTypes   = array_unique($toProperty->getDocBlockTypeStrings());

        sort($fromTypes);
        sort($toTypes);

        if ($fromTypes === $toTypes) {
            return Changes::empty();
        }

        return Changes::fromArray([
            Change::changed(
                sprintf(
                    'Type documentation for property %s changed from %s to %s',
                    $this->formatProperty->__invoke($fromProperty),
                    implode('|', $fromTypes) ?: 'having no type',
                    implode('|', $toTypes) ?: 'having no type'
                ),
                true
            ),
        ]);
    }
}
