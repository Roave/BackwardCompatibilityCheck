<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\FunctionBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Formatter\ReflectionFunctionAbstractName;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use function sprintf;

/**
 * PHP still (sadly) supports by-ref return types, so the type is wildly different between by-ref and by-val, and
 * a change in such a signature is a breakage
 */
final class ReturnTypeByReferenceChanged implements FunctionBased
{
    /** @var ReflectionFunctionAbstractName */
    private $formatFunction;

    public function __construct()
    {
        $this->formatFunction = new ReflectionFunctionAbstractName();
    }

    public function __invoke(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction) : Changes
    {
        $fromReturnsReference = $fromFunction->returnsReference();
        $toReturnsReference   = $toFunction->returnsReference();

        if ($fromReturnsReference === $toReturnsReference) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            sprintf(
                'The return value of %s changed from %s to %s',
                $this->formatFunction->__invoke($fromFunction),
                $this->referenceToString($fromReturnsReference),
                $this->referenceToString($toReturnsReference)
            ),
            true
        ));
    }

    private function referenceToString(bool $reference) : string
    {
        return $reference ? 'by-reference' : 'by-value';
    }
}
