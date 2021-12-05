<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\FunctionName;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;

/**
 * When new parameters are added, they must be optional, or else the callers will provide an insufficient
 * amount of parameters to the function.
 */
final class RequiredParameterAmountIncreased implements FunctionBased
{
    private FunctionName $formatFunction;

    public function __construct()
    {
        $this->formatFunction = new FunctionName();
    }

    public function __invoke(
        ReflectionMethod|ReflectionFunction $fromFunction,
        ReflectionMethod|ReflectionFunction $toFunction
    ): Changes {
        $fromRequiredParameters = $fromFunction->getNumberOfRequiredParameters();
        $toRequiredParameters   = $toFunction->getNumberOfRequiredParameters();

        if ($fromRequiredParameters >= $toRequiredParameters) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'The number of required arguments for %s increased from %d to %d',
                ($this->formatFunction)($fromFunction),
                $fromRequiredParameters,
                $toRequiredParameters
            ),
            true
        ));
    }
}
