<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function array_intersect_key;
use function array_keys;

final class PropertyChanged implements ClassBased
{
    /** @var PropertyBased */
    private $checkProperty;

    public function __construct(PropertyBased $checkProperty)
    {
        $this->checkProperty = $checkProperty;
    }

    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        $propertiesFrom   = $fromClass->getProperties();
        $propertiesTo     = $toClass->getProperties();
        $commonProperties = array_keys(array_intersect_key($propertiesFrom, $propertiesTo));

        return Changes::fromIterator((function () use ($propertiesFrom, $propertiesTo, $commonProperties) {
            foreach ($commonProperties as $propertyName) {
                yield from $this->checkProperty->__invoke($propertiesFrom[$propertyName], $propertiesTo[$propertyName]);
            }
        })());
    }
}
