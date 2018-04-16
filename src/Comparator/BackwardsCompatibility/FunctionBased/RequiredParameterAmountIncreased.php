<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;

/**
 * When new parameters are added, they must be optional, or else the callers will provide an insufficient
 * amount of parameters to the function.
 */
final class RequiredParameterAmountIncreased implements FunctionBased
{
    public function compare(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction) : Changes
    {
        $fromRequiredParameters = $fromFunction->getNumberOfRequiredParameters();
        $toRequiredParameters   = $toFunction->getNumberOfRequiredParameters();

        if ($fromRequiredParameters >= $toRequiredParameters) {
            return Changes::new();
        }

        return Changes::fromArray([
            Change::changed(
                sprintf(
                    'The number of required arguments for %s increased from %d to %d',
                    $this->functionOrMethodName($fromFunction),
                    $fromRequiredParameters,
                    $toRequiredParameters
                ),
                true
            ),
        ]);
    }

    private function functionOrMethodName(ReflectionFunctionAbstract $function) : string
    {
        if ($function instanceof ReflectionMethod) {
            return $function->getDeclaringClass()->getName()
                . ($function->isStatic() ? '::' : '#')
                . $function->getName();
        }

        return $function->getName();
    }
}
