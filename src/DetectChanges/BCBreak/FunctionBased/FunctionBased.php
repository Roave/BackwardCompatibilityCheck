<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\FunctionBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;

interface FunctionBased
{
    public function __invoke(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction) : Changes;
}
