<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\FunctionBased;

use Roave\ApiCompare\Changes;
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

    public function __invoke(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toClass) : Changes
    {
        return array_reduce(
            $this->checks,
            function (Changes $changes, FunctionBased $check) use ($fromFunction, $toClass) : Changes {
                return $changes->mergeWith($check->__invoke($fromFunction, $toClass));
            },
            Changes::empty()
        );
    }
}
