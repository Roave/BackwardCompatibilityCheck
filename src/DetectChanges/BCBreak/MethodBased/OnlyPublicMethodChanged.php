<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\MethodBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionMethod;

/**
 * Performs a method BC compliance check on methods that are public
 */
final class OnlyPublicMethodChanged implements MethodBased
{
    /** @var MethodBased */
    private $check;

    public function __construct(MethodBased $check)
    {
        $this->check = $check;
    }

    public function __invoke(ReflectionMethod $fromMethod, ReflectionMethod $toMethod) : Changes
    {
        if (! $fromMethod->isPublic()) {
            return Changes::new();
        }

        return $this->check->__invoke($fromMethod, $toMethod);
    }
}
