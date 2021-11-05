<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Psl\Dict;
use Psl\Vec;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased\ClassConstantBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

final class ConstantChanged implements ClassBased
{
    private ClassConstantBased $checkConstant;

    public function __construct(ClassConstantBased $checkConstant)
    {
        $this->checkConstant = $checkConstant;
    }

    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass): Changes
    {
        return Changes::fromIterator($this->checkSymbols(
            $fromClass->getReflectionConstants(),
            $toClass->getReflectionConstants()
        ));
    }

    /**
     * @param ReflectionClassConstant[] $from
     * @param ReflectionClassConstant[] $to
     *
     * @return iterable<int, Change>
     */
    private function checkSymbols(array $from, array $to): iterable
    {
        foreach (Vec\keys(Dict\intersect_by_key($from, $to)) as $name) {
            yield from ($this->checkConstant)($from[$name], $to[$name]);
        }
    }
}
