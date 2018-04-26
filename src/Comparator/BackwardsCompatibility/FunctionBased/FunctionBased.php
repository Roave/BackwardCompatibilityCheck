<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;

interface FunctionBased
{
    public function compare(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction) : Changes;
}
