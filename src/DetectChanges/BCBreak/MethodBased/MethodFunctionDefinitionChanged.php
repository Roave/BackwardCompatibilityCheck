<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\FunctionBased;
use Roave\BetterReflection\Reflection\ReflectionMethod;

/**
 * Performs a function BC compliance check on a method
 */
final class MethodFunctionDefinitionChanged implements MethodBased
{
    private FunctionBased $functionCheck;

    public function __construct(FunctionBased $functionCheck)
    {
        $this->functionCheck = $functionCheck;
    }

    public function __invoke(ReflectionMethod $fromMethod, ReflectionMethod $toMethod) : Changes
    {
        return $this->functionCheck->__invoke($fromMethod, $toMethod);
    }
}
