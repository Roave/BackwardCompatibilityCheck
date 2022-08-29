<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Throwable;

final class SkipInterfaceBasedErrors implements InterfaceBased
{
    public function __construct(private InterfaceBased $next)
    {
    }

    public function __invoke(ReflectionClass $fromInterface, ReflectionClass $toInterface): Changes
    {
        try {
            return ($this->next)($fromInterface, $toInterface);
        } catch (Throwable $failure) {
            return Changes::fromList(Change::skippedDueToFailure($failure));
        }
    }
}
