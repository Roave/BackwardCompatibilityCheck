<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\ReflectionFunctionAbstractName;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Psl\Dict;
use Psl\Str;

/**
 * A parameter passed by-value and a parameter passed by-reference are wildly different, so changing
 * the by-ref flag can lead to unexpected state mutations or lack thereof, and should therefore be
 * considered a BC break.
 */
final class ParameterByReferenceChanged implements FunctionBased
{
    private ReflectionFunctionAbstractName $formatFunction;

    public function __construct()
    {
        $this->formatFunction = new ReflectionFunctionAbstractName();
    }

    public function __invoke(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction): Changes
    {
        $fromParameters = $fromFunction->getParameters();
        $toParameters   = $toFunction->getParameters();
        $changes        = Changes::empty();

        foreach (Dict\intersect_by_key($fromParameters, $toParameters) as $parameterIndex => $commonParameter) {
            $changes = $changes->mergeWith($this->compareParameter($commonParameter, $toParameters[$parameterIndex]));
        }

        return $changes;
    }

    private function compareParameter(ReflectionParameter $fromParameter, ReflectionParameter $toParameter): Changes
    {
        $fromByReference = $fromParameter->isPassedByReference();
        $toByReference   = $toParameter->isPassedByReference();

        if ($fromByReference === $toByReference) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'The parameter $%s of %s changed from %s to %s',
                $fromParameter->getName(),
                $this->formatFunction->__invoke($fromParameter->getDeclaringFunction()),
                $this->referenceToString($fromByReference),
                $this->referenceToString($toByReference)
            ),
            true
        ));
    }

    private function referenceToString(bool $reference): string
    {
        return $reference ? 'by-reference' : 'by-value';
    }
}
