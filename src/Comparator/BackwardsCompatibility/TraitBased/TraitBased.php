<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\TraitBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

interface TraitBased
{
    public function compare(ReflectionClass $fromInterface, ReflectionClass $toInterface) : Changes;
}
