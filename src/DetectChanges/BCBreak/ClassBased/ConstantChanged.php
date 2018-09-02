<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased\ClassConstantBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function array_intersect_key;
use function array_keys;

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
        $commonConstants = array_keys(array_intersect_key($constantsFrom, $constantsTo));

        return Changes::fromIterator((function () use ($constantsFrom, $constantsTo, $commonConstants) {
            foreach ($commonConstants as $constantName) {
                yield from $this->checkConstant->__invoke($constantsFrom[$constantName], $constantsTo[$constantName]);
            }
        })());
    }
}
