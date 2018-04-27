<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionMethod;

/**
 * Performs a method BC compliance check on methods that are visible
 */
final class AccessibleMethodChange implements MethodBased
{
    /** @var MethodBased */
    private $check;

    public function __construct(MethodBased $check)
    {
        $this->check = $check;
    }

    public function __invoke(ReflectionMethod $fromMethod, ReflectionMethod $toMethod) : Changes
    {
        if ($fromMethod->isPrivate()) {
            return Changes::empty();
        }

        return $this->check->__invoke($fromMethod, $toMethod);
    }
}
