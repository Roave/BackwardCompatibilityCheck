<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\MethodBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionMethod;

interface MethodBased
{
    public function __invoke(ReflectionMethod $fromMethod, ReflectionMethod $toMethod) : Changes;
}
