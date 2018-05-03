<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Throwable;

final class SkipClassBasedErrors implements ClassBased
{
    private ClassBased $next;

    public function __construct(ClassBased $next)
    {
        $this->next = $next;
    }

    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass): Changes
    {
        try {
            return ($this->next)($fromClass, $toClass);
        } catch (Throwable $failure) {
            return Changes::fromList(Change::skippedDueToFailure($failure));
        }
    }
}
