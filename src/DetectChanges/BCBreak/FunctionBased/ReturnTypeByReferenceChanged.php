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
 * PHP still (sadly) supports by-ref return types, so the type is wildly different between by-ref and by-val, and
 * a change in such a signature is a breakage
 */
final class ReturnTypeByReferenceChanged implements FunctionBased
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
        $fromReturnsReference = $fromFunction->returnsReference();
        $toReturnsReference   = $toFunction->returnsReference();

        if ($fromReturnsReference === $toReturnsReference) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'The return value of %s changed from %s to %s',
                ($this->formatFunction)($fromFunction),
                $this->referenceToString($fromReturnsReference),
                $this->referenceToString($toReturnsReference)
            )
        ));
    }

    private function referenceToString(bool $reference): string
    {
        return $reference ? 'by-reference' : 'by-value';
    }
}
