<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Throwable;

final class SkipInterfaceBasedErrors implements InterfaceBased
{
    /** @var InterfaceBased */
    private $next;

    public function __construct(InterfaceBased $next)
    {
        $this->next = $next;
    }

    public function __invoke(ReflectionClass $fromInterface, ReflectionClass $toInterface) : Changes
    {
        try {
            return $this->next->__invoke($fromInterface, $toInterface);
        } catch (Throwable $failure) {
            return Changes::fromList(Change::skippedDueToFailure($failure));
        }
    }
}
