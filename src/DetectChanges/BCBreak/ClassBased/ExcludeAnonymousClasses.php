<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class ExcludeAnonymousClasses implements ClassBased
{
    private ClassBased $check;

    public function __construct(ClassBased $check)
    {
        $this->check = $check;
    }

    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass): Changes
    {
        if ($fromClass->isAnonymous()) {
            return Changes::empty();
        }

        return ($this->check)($fromClass, $toClass);
    }
}
