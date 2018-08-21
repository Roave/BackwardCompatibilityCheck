<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Throwable;

final class SkipClassConstantBasedErrors implements ClassConstantBased
{
    /** @var ClassConstantBased */
    private $next;

    public function __construct(ClassConstantBased $next)
    {
        $this->next = $next;
    }

    public function __invoke(ReflectionClassConstant $fromConstant, ReflectionClassConstant $toConstant) : Changes
    {
        try {
            return $this->next->__invoke($fromConstant, $toConstant);
        } catch (Throwable $failure) {
            return Changes::fromList(Change::skippedDueToFailure($failure));
        }
    }
}
