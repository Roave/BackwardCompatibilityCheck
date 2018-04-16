<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\MethodBased;

use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased\FunctionBased;
use Roave\BetterReflection\Reflection\ReflectionMethod;

/**
 * Performs a function BC compliance check on methods that are visible
 */
final class AccessibleMethodFunctionBasedChange implements MethodBased
{
    /** @var FunctionBased */
    private $functionCheck;

    public function __construct(FunctionBased $functionCheck)
    {
        $this->functionCheck = $functionCheck;
    }

    public function compare(ReflectionMethod $fromMethod, ReflectionMethod $toMethod) : Changes
    {
        if ($fromMethod->isPrivate()) {
            return Changes::new();
        }

        return $this->functionCheck->compare($fromMethod, $toMethod);
    }
}
