<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

interface ClassBased
{
    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass): Changes;
}
