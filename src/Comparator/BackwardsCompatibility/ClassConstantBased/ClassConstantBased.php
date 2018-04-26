<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassConstantBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

interface ClassConstantBased
{
    public function compare(ReflectionClassConstant $fromConstant, ReflectionClassConstant $toConstant) : Changes;
}
