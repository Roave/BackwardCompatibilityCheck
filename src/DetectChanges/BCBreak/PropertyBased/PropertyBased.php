<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionProperty;

interface PropertyBased
{
    public function __invoke(ReflectionProperty $fromProperty, ReflectionProperty $toProperty) : Changes;
}
