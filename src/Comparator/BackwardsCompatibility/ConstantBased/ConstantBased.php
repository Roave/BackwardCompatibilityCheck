<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\ConstantBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

interface ConstantBased
{
    public function compare(ReflectionClassConstant $fromConstant, ReflectionClassConstant $toConstant) : Changes;
}
