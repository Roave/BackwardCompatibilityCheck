<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\MethodBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionMethod;

/**
 * Performs a method BC compliance check on methods that are protected
 */
final class OnlyProtectedMethodChange implements MethodBased
{
    /** @var MethodBased */
    private $check;

    public function __construct(MethodBased $check)
    {
        $this->check = $check;
    }

    public function compare(ReflectionMethod $fromMethod, ReflectionMethod $toMethod) : Changes
    {
        if (! $fromMethod->isProtected()) {
            return Changes::new();
        }

        return $this->check->compare($fromMethod, $toMethod);
    }
}
