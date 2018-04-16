<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;

/**
 * A parameter passed by-value and a parameter passed by-reference are wildly different, so changing
 * the by-ref flag can lead to unexpected state mutations or lack thereof, and should therefore be
 * considered a BC break.
 */
final class ParameterByReferenceChanged implements FunctionBased
{
    public function compare(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction) : Changes
    {
        /** @var $fromParameters ReflectionParameter[] */
        $fromParameters = array_values($fromFunction->getParameters());
        /** @var $toParameters ReflectionParameter[] */
        $toParameters = array_values($toFunction->getParameters());

        $changes = Changes::new();

        foreach (array_intersect_key($fromParameters, $toParameters) as $parameterIndex => $commonParameter) {
            $changes = $changes->mergeWith($this->compareParameter($commonParameter, $toParameters[$parameterIndex]));
        }

        return $changes;
    }

    private function compareParameter(ReflectionParameter $fromParameter, ReflectionParameter $toParameter) : Changes
    {
        $fromByReference = $fromParameter->isPassedByReference();
        $toByReference   = $toParameter->isPassedByReference();

        if ($fromByReference === $toByReference) {
            return Changes::new();
        }

        return Changes::fromArray([
            Change::changed(
                sprintf(
                    'The parameter $%s of %s() changed from %s to %s',
                    $fromParameter->getName(),
                    $this->functionOrMethodName($fromParameter->getDeclaringFunction()),
                    $this->referenceToString($fromByReference),
                    $this->referenceToString($toByReference)
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