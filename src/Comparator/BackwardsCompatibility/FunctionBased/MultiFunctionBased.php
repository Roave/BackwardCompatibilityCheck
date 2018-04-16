<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;

final class MultiFunctionBased implements FunctionBased
{
    /** @var FunctionBased[] */
    private $checks;

    public function __construct(FunctionBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function compare(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toClass) : Changes
    {
        return array_reduce(
            $this->checks,
            function (Changes $changes, FunctionBased $check) use ($fromFunction, $toClass) : Changes {
                return $changes->mergeWith($check->compare($fromFunction, $toClass));
            },
            Changes::new()
        );
    }
}