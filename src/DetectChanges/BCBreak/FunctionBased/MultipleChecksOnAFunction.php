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
        return array_reduce(
            $this->checks,
            function (Changes $changes, FunctionBased $check) use ($fromFunction, $toFunction) : Changes {
                return $changes->mergeWith($check->__invoke($fromFunction, $toFunction));
            },
            Changes::empty()
        );
    }
}
