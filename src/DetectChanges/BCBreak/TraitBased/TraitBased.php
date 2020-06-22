<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

interface TraitBased
{
    public function __invoke(ReflectionClass $fromTrait, ReflectionClass $toTrait): Changes;
}
