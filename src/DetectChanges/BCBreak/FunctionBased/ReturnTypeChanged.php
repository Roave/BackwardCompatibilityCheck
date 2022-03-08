<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\FunctionName;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionType;

/**
 * Verifies if the return type of a function changed at all
 *
 * This is useful when comparing methods of interfaces and non-final classes, where child classes may be affected.
 */
final class ReturnTypeChanged implements FunctionBased
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
        $fromReturnType = $this->typeToString($fromFunction->getReturnType());
        $toReturnType   = $this->typeToString($toFunction->getReturnType());

        if ($fromReturnType === $toReturnType) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'The return type of %s changed from %s to %s',
                ($this->formatFunction)($fromFunction),
                $fromReturnType,
                $toReturnType
            )
        ));
    }

    private function typeToString(?ReflectionType $type): string
    {
        if (! $type) {
            return 'no type';
        }

        return $type->__toString();
    }
}
