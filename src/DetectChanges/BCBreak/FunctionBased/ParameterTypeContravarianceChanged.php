<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Psl\Dict;
use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsContravariant;
use Roave\BackwardCompatibility\Formatter\FunctionName;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType;

/**
 * When a parameter type changes, the new type should be wider than the previous type, or else
 * the callers will be passing invalid data to the function.
 */
final class ParameterTypeContravarianceChanged implements FunctionBased
{
    private TypeIsContravariant $typeIsContravariant;

    private FunctionName $formatFunction;

    public function __construct(TypeIsContravariant $typeIsContravariant)
    {
        $this->typeIsContravariant = $typeIsContravariant;
        $this->formatFunction      = new FunctionName();
    }

    public function __invoke(
        ReflectionMethod|ReflectionFunction $fromFunction,
        ReflectionMethod|ReflectionFunction $toFunction
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
        $fromType = $fromParameter->getType();
        $toType   = $toParameter->getType();

        if (($this->typeIsContravariant)($fromType, $toType)) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'The parameter $%s of %s changed from %s to a non-contravariant %s',
                $fromParameter->getName(),
                ($this->formatFunction)($fromParameter->getDeclaringFunction()),
                $this->typeToString($fromType),
                $this->typeToString($toType)
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
