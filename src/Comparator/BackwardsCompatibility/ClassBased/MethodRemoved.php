<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use Assert\Assert;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use const CASE_UPPER;
use function array_change_key_case;
use function array_combine;
use function array_diff_key;
use function array_filter;
use function array_map;
use function array_values;
use function sprintf;

final class MethodRemoved implements ClassBased
{
    public function compare(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        Assert::that($fromClass->getName())->same($toClass->getName());

        $removedMethods = array_diff_key(
            array_change_key_case($this->accessibleMethods($fromClass), CASE_UPPER),
            array_change_key_case($this->accessibleMethods($toClass), CASE_UPPER)
        );

        return Changes::fromArray(array_values(array_map(function (ReflectionMethod $method) use ($fromClass) : Change {
            return Change::removed(
                sprintf('Method %s#%s() was removed', $fromClass->getName(), $method->getName()),
                true
            );
        }, $removedMethods)));
    }

    /** @return ReflectionMethod[] */
    private function accessibleMethods(ReflectionClass $class) : array
    {
        $methods = array_filter($class->getMethods(), function (ReflectionMethod $method) : bool {
            return $method->isPublic() || $method->isProtected();
        });

        return array_combine(
            array_map(function (ReflectionMethod $method) : string {
                return $method->getName();
            }, $methods),
            $methods
        );
    }
}
