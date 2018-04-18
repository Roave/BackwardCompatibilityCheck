<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassConstantBased\ConstantBased;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class ConstantChanged implements ClassBased
{
    /** @var ConstantBased */
    private $checkConstant;

    public function __construct(ConstantBased $checkConstant)
    {
        $this->checkConstant = $checkConstant;
    }

    public function compare(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        $constantsFrom   = $fromClass->getReflectionConstants();
        $constantsTo     = $toClass->getReflectionConstants();
        $commonConstants = array_intersect_key($constantsFrom, $constantsTo);

        return array_reduce(
            array_keys($commonConstants),
            function (Changes $accumulator, string $constantName) use ($constantsFrom, $constantsTo) : Changes {
                return $accumulator->mergeWith($this->checkConstant->compare(
                    $constantsFrom[$constantName],
                    $constantsTo[$constantName]
                ));
            },
            Changes::new()
        );
    }
}
