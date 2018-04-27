<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\ClassBased;

use Roave\ApiCompare\Changes;
use Roave\ApiCompare\DetectChanges\BCBreak\ClassConstantBased\ClassConstantBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function array_intersect_key;
use function array_keys;
use function array_reduce;

final class ConstantChanged implements ClassBased
{
    /** @var ClassConstantBased */
    private $checkConstant;

    public function __construct(ClassConstantBased $checkConstant)
    {
        $this->checkConstant = $checkConstant;
    }

    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        $constantsFrom   = $fromClass->getReflectionConstants();
        $constantsTo     = $toClass->getReflectionConstants();
        $commonConstants = array_intersect_key($constantsFrom, $constantsTo);

        return array_reduce(
            array_keys($commonConstants),
            function (Changes $accumulator, string $constantName) use ($constantsFrom, $constantsTo) : Changes {
                return $accumulator->mergeWith($this->checkConstant->__invoke(
                    $constantsFrom[$constantName],
                    $constantsTo[$constantName]
                ));
            },
            Changes::empty()
        );
    }
}
