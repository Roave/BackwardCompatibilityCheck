<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Throwable;

final class SkipFunctionBasedErrors implements FunctionBased
{
    private FunctionBased $next;

    public function __construct(FunctionBased $next)
    {
        $this->next = $next;
    }

    public function __invoke(
        ReflectionMethod|ReflectionFunction $fromFunction,
        ReflectionMethod|ReflectionFunction $toFunction
    ): Changes {
        try {
            return ($this->next)($fromFunction, $toFunction);
        } catch (Throwable $failure) {
            return Changes::fromList(Change::skippedDueToFailure($failure));
        }
    }
}
