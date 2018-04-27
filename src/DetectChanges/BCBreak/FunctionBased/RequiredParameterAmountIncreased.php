<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\FunctionBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Formatter\ReflectionFunctionAbstractName;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use function sprintf;

/**
 * When new parameters are added, they must be optional, or else the callers will provide an insufficient
 * amount of parameters to the function.
 */
final class RequiredParameterAmountIncreased implements FunctionBased
{
    /** @var ReflectionFunctionAbstractName */
    private $formatFunction;

    public function __construct()
    {
        $this->formatFunction = new ReflectionFunctionAbstractName();
    }

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
                    $this->formatFunction->__invoke($fromFunction),
                    $fromRequiredParameters,
                    $toRequiredParameters
                ),
                true
            ),
        ]);
    }
}
