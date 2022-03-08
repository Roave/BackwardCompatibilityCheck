<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsCovariant;
use Roave\BackwardCompatibility\Formatter\FunctionName;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionType;

/**
 * When the return type of a function changes, the new return type must be covariant to the current type.
 *
 * If that's not the case, then consumers of the API will be presented with values that they cannot work with.
 */
final class ReturnTypeCovarianceChanged implements FunctionBased
{
    private TypeIsCovariant $typeIsCovariant;

    private FunctionName $formatFunction;

    public function __construct(TypeIsCovariant $typeIsCovariant)
    {
        $this->typeIsCovariant = $typeIsCovariant;
        $this->formatFunction  = new FunctionName();
    }

    public function __invoke(
        ReflectionMethod|ReflectionFunction $fromFunction,
        ReflectionMethod|ReflectionFunction $toFunction
    ): Changes {
        $fromReturnType = $fromFunction->getReturnType();
        $toReturnType   = $toFunction->getReturnType();

        if (($this->typeIsCovariant)($fromReturnType, $toReturnType)) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'The return type of %s changed from %s to the non-covariant %s',
                ($this->formatFunction)($fromFunction),
                $this->typeToString($fromReturnType),
                $this->typeToString($toReturnType)
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
