<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\ClassBased;

use Roave\ApiCompare\Changes;
use Roave\ApiCompare\DetectChanges\BCBreak\PropertyBased\PropertyBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function array_intersect_key;
use function array_keys;
use function array_reduce;

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
        $commonProperties = array_intersect_key($propertiesFrom, $propertiesTo);

        return array_reduce(
            array_keys($commonProperties),
            function (Changes $accumulator, string $propertyName) use ($propertiesFrom, $propertiesTo) : Changes {
                return $accumulator->mergeWith($this->checkProperty->__invoke(
                    $propertiesFrom[$propertyName],
                    $propertiesTo[$propertyName]
                ));
            },
            Changes::new()
        );
    }
}
