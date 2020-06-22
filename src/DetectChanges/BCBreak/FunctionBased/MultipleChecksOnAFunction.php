<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;

final class MultipleChecksOnAFunction implements FunctionBased
{
    /** @var FunctionBased[] */
    private array $checks;

    public function __construct(FunctionBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function __invoke(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction): Changes
    {
        return Changes::fromIterator($this->multipleChecks($fromFunction, $toFunction));
    }

    /** @return iterable|Change[] */
    private function multipleChecks(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction): iterable
    {
        foreach ($this->checks as $check) {
            yield from $check->__invoke($fromFunction, $toFunction);
        }
    }
}
