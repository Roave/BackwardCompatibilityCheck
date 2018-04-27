<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\PropertyBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionProperty;

interface PropertyBased
{
    public function compare(ReflectionProperty $fromProperty, ReflectionProperty $toProperty) : Changes;
}
