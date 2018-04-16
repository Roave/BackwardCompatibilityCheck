<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;

/**
 * When new parameters are added, they must be optional, or else the callers will provide an insufficient
 * amount of parameters to the function.
 */
final class RequiredParameterAmountIncreased implements FunctionBased
{
    public function compare(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction) : Changes
    {
        $fromRequiredParameters = $this->lastRequiredParameterPosition($fromFunction);
        $toRequiredParameters   = $this->lastRequiredParameterPosition($toFunction);

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

    public function lastRequiredParameterPosition(ReflectionFunctionAbstract $function) : int
    {
        return max(
            0,
            0,
            ...array_values(array_map(
                function (ReflectionParameter $parameter) : int {
                    return $parameter->getPosition();
                },
                array_filter($function->getParameters(), function (ReflectionParameter $parameter) : bool {
                    return ! $parameter->isOptional();
                })
            ))
        );
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
