<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\ClassBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

interface ClassBased
{
    public function compare(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes;
}
