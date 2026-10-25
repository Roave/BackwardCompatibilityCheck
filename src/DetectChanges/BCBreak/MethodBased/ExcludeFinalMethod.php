<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionMethod;

/**
 * Methods marked "final" cannot be overridden by child classes, and are therefore
 * not affected by BC checks that only protect overriding methods, such as checks
 * verifying that parameter and return types did not change at all.
 */
final class ExcludeFinalMethod implements MethodBased
{
    public function __construct(private MethodBased $check)
    {
    }

    public function __invoke(ReflectionMethod $fromMethod, ReflectionMethod $toMethod): Changes
    {
        if ($fromMethod->isFinal()) {
            return Changes::empty();
        }

        return ($this->check)($fromMethod, $toMethod);
    }
}
