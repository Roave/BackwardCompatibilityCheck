<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;

/**
 * PHP still (sadly) supports by-ref return types, so the type is wildly different between by-ref and by-val, and
 * a change in such a signature is a breakage
 */
final class ReturnTypeByReferenceChanged implements FunctionBased
{
    public function compare(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction) : Changes
    {
        $fromReturnsReference = $fromFunction->returnsReference();
        $toReturnsReference   = $toFunction->returnsReference();

        if ($fromReturnsReference === $toReturnsReference) {
            return Changes::new();
        }

        return Changes::fromArray([
            Change::changed(
                sprintf(
                    'The return value of %s() changed from %s to %s',
                    $this->functionOrMethodName($fromFunction),
                    $this->referenceToString($fromReturnsReference),
                    $this->referenceToString($toReturnsReference)
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

    private function referenceToString(bool $reference) : string
    {
        return $reference ? 'by-reference' : 'by-value';
    }
}
