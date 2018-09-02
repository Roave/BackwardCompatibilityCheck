<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use function array_reduce;

final class MultipleChecksOnAFunction implements FunctionBased
{
    /** @var FunctionBased[] */
    private $checks;

    public function __construct(FunctionBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function __invoke(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction) : Changes
    {
        return Changes::fromIterator((function () use ($fromFunction, $toFunction) : iterable {
            foreach ($this->checks as $check) {
                yield from $check->__invoke($fromFunction, $toFunction);
            }
        })());
    }
}
