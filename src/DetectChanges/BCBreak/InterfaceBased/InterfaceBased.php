<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

interface InterfaceBased
{
    public function __invoke(ReflectionClass $fromInterface, ReflectionClass $toInterface) : Changes;
}
