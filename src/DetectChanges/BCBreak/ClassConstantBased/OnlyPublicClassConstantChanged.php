<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

final class OnlyPublicClassConstantChanged implements ClassConstantBased
{
    /** @var ClassConstantBased */
    private $constantCheck;

    public function __construct(ClassConstantBased $constantCheck)
    {
        $this->constantCheck = $constantCheck;
    }

    public function __invoke(ReflectionClassConstant $fromConstant, ReflectionClassConstant $toConstant) : Changes
    {
        if (! $fromConstant->isPublic()) {
            return Changes::empty();
        }

        return $this->constantCheck->__invoke($fromConstant, $toConstant);
    }
}
