<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Throwable;

final class SkipTraitBasedErrors implements TraitBased
{
    public function __construct(private TraitBased $next)
    {
    }

    public function __invoke(ReflectionClass $fromTrait, ReflectionClass $toTrait): Changes
    {
        try {
            return ($this->next)($fromTrait, $toTrait);
        } catch (Throwable $failure) {
            return Changes::fromList(Change::skippedDueToFailure($failure));
        }
    }
}
