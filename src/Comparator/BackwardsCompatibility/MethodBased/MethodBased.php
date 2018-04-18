<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\MethodBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionMethod;

interface MethodBased
{
    public function compare(ReflectionMethod $fromMethod, ReflectionMethod $toMethod) : Changes;
}
