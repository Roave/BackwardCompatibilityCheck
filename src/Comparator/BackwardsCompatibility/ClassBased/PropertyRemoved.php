<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use Assert\Assert;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionProperty;

final class PropertyRemoved implements ClassBased
{
    public function compare(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        Assert::that($fromClass->getName())->same($toClass->getName());

        $removedProperties = array_diff(
            array_keys($this->accessibleProperties($fromClass)),
            array_keys($this->accessibleProperties($toClass))
        );

        return Changes::fromArray(array_values(array_map(function (string $property) use ($fromClass) : Change {
            return Change::removed(
                sprintf('Property %s#%s was removed', $fromClass->getName(), $property),
                true
            );
        }, $removedProperties)));
    }

    /** @return ReflectionProperty[] */
    private function accessibleProperties(ReflectionClass $class) : array
    {
        return array_filter($class->getProperties(), function (ReflectionProperty $property) : bool {
            return $property->isPublic() || $property->isProtected();
        });
    }
}