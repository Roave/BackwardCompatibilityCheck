<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\ReflectionFunctionAbstractName;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Psl\Str;

/**
 * When new parameters are added, they must be optional, or else the callers will provide an insufficient
 * amount of parameters to the function.
 */
final class RequiredParameterAmountIncreased implements FunctionBased
{
    private ReflectionFunctionAbstractName $formatFunction;

    public function __construct()
    {
        $this->formatFunction = new ReflectionFunctionAbstractName();
    }

    public function __invoke(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction): Changes
    {
        $fromRequiredParameters = $fromFunction->getNumberOfRequiredParameters();
        $toRequiredParameters   = $toFunction->getNumberOfRequiredParameters();

        if ($fromRequiredParameters >= $toRequiredParameters) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'The number of required arguments for %s increased from %d to %d',
                $this->formatFunction->__invoke($fromFunction),
                $fromRequiredParameters,
                $toRequiredParameters
            ),
            true
        ));
    }
}
