<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\InterfaceBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

interface InterfaceBased
{
    public function compare(ReflectionClass $fromInterface, ReflectionClass $toInterface) : Changes;
}
