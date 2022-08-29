<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

final class OnlyProtectedClassConstantChanged implements ClassConstantBased
{
    public function __construct(private ClassConstantBased $constantCheck)
    {
    }

    public function __invoke(ReflectionClassConstant $fromConstant, ReflectionClassConstant $toConstant): Changes
    {
        if (! $fromConstant->isProtected()) {
            return Changes::empty();
        }

        return ($this->constantCheck)($fromConstant, $toConstant);
    }
}
