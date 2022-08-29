<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Psl\Dict;
use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\FunctionName;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;

/**
 * A parameter passed by-value and a parameter passed by-reference are wildly different, so changing
 * the by-ref flag can lead to unexpected state mutations or lack thereof, and should therefore be
 * considered a BC break.
 */
final class ParameterByReferenceChanged implements FunctionBased
{
    private FunctionName $formatFunction;

    public function __construct()
    {
        $this->formatFunction = new FunctionName();
    }

    public function __invoke(
        ReflectionMethod|ReflectionFunction $fromFunction,
        ReflectionMethod|ReflectionFunction $toFunction,
    ): Changes {
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
                ($this->formatFunction)($fromParameter->getDeclaringFunction()),
                $this->referenceToString($fromByReference),
                $this->referenceToString($toByReference),
            ),
        ));
    }

    private function referenceToString(bool $reference): string
    {
        return $reference ? 'by-reference' : 'by-value';
    }
}
