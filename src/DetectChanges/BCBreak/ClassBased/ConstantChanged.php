<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased\ClassConstantBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function array_intersect_key;
use function array_keys;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

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
        return Changes::fromIterator($this->checkSymbols(
            $fromClass->getReflectionConstants(),
            $toClass->getReflectionConstants()
        ));
    }

    /**
     * @param array<string, ReflectionClassConstant> $from
     * @param array<string, ReflectionClassConstant> $to
     *
     * @return iterable|Change[]
     */
    private function checkSymbols(array $from, array $to) : iterable
    {
        foreach (array_keys(array_intersect_key($from, $to)) as $name) {
            yield from $this->checkConstant->__invoke($from[$name], $to[$name]);
        }
    }
}
