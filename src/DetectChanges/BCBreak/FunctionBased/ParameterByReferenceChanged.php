<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\FunctionBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Formatter\ReflectionFunctionAbstractName;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use function array_intersect_key;
use function array_values;
use function sprintf;

/**
 * A parameter passed by-value and a parameter passed by-reference are wildly different, so changing
 * the by-ref flag can lead to unexpected state mutations or lack thereof, and should therefore be
 * considered a BC break.
 */
final class ParameterByReferenceChanged implements FunctionBased
{
    /** @var ReflectionFunctionAbstractName */
    private $formatFunction;

    public function __construct()
    {
        $this->formatFunction = new ReflectionFunctionAbstractName();
    }

    public function __invoke(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction) : Changes
    {
        /** @var ReflectionParameter[] $fromParameters */
        $fromParameters = array_values($fromFunction->getParameters());
        /** @var ReflectionParameter[] $toParameters */
        $toParameters = array_values($toFunction->getParameters());

        $changes = Changes::empty();

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
            return Changes::empty();
        }

        return Changes::fromArray([
            Change::changed(
                sprintf(
                    'The parameter $%s of %s changed from %s to %s',
                    $fromParameter->getName(),
                    $this->formatFunction->__invoke($fromParameter->getDeclaringFunction()),
                    $this->referenceToString($fromByReference),
                    $this->referenceToString($toByReference)
                ),
                true
            ),
        ]);
    }

    private function referenceToString(bool $reference) : string
    {
        return $reference ? 'by-reference' : 'by-value';
    }
}
