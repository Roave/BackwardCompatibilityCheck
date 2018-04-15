<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use Assert\Assert;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

final class ConstantValueChanged implements ClassBased
{
    public function compare(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        Assert::that($fromClass->getName())->same($toClass->getName());

        $fromValues = $this->accessibleConstantValues($fromClass);
        $toValues   = $this->accessibleConstantValues($toClass);

        $changedConstants = array_keys(array_filter($fromValues, function ($constantValue, string $constantName) use (
            $toValues
        ) : bool {
            return array_key_exists($constantName, $toValues) && $constantValue !== $toValues[$constantName];
        }, \ARRAY_FILTER_USE_BOTH));

        return Changes::fromArray(array_values(array_map(function (string $constantName) use ($fromClass) : Change {
            return Change::changed(
                sprintf('Value of constant %s::%s changed', $fromClass->getName(), $constantName),
                true
            );
        }, $changedConstants)));
    }

    /** @return ReflectionClassConstant[] */
    private function accessibleConstantValues(ReflectionClass $class) : array
    {
        $accessibleConstants = array_filter(
            $class->getReflectionConstants(),
            function (ReflectionClassConstant $constant) : bool {
                return $constant->isPublic() || $constant->isProtected();
            }
        );

        return array_map(function (ReflectionClassConstant $constant) {
            return $constant->getValue();
        }, $accessibleConstants);
    }
}