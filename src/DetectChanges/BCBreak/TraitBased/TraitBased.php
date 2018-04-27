<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\TraitBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

interface TraitBased
{
    public function compare(ReflectionClass $fromTrait, ReflectionClass $toTrait) : Changes;
}
