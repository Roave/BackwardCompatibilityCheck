<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsContravariant;
use Roave\BackwardCompatibility\Formatter\ReflectionFunctionAbstractName;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType;

use function array_intersect_key;
use function Safe\sprintf;

/**
 * When a parameter type changes, the new type should be wider than the previous type, or else
 * the callers will be passing invalid data to the function.
 */
final class ParameterTypeContravarianceChanged implements FunctionBased
{
    private TypeIsContravariant $typeIsContravariant;

    private ReflectionFunctionAbstractName $formatFunction;

    public function __construct(TypeIsContravariant $typeIsContravariant)
    {
        $this->typeIsContravariant = $typeIsContravariant;
        $this->formatFunction      = new ReflectionFunctionAbstractName();
    }

    public function __invoke(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction): Changes
    {
        $fromParameters = $fromFunction->getParameters();
        $toParameters   = $toFunction->getParameters();
        $changes        = Changes::empty();

        foreach (array_intersect_key($fromParameters, $toParameters) as $parameterIndex => $commonParameter) {
            $changes = $changes->mergeWith($this->compareParameter($commonParameter, $toParameters[$parameterIndex]));
        }

        return $changes;
    }

    private function compareParameter(ReflectionParameter $fromParameter, ReflectionParameter $toParameter): Changes
    {
        $fromType = $fromParameter->getType();
        $toType   = $toParameter->getType();

        if ($this->typeIsContravariant->__invoke($fromType, $toType)) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            sprintf(
                'The parameter $%s of %s changed from %s to a non-contravariant %s',
                $fromParameter->getName(),
                $this->formatFunction->__invoke($fromParameter->getDeclaringFunction()),
                $this->typeToString($fromType),
                $this->typeToString($toType)
            ),
            true
        ));
    }

    private function typeToString(?ReflectionType $type): string
    {
        if (! $type) {
            return 'no type';
        }

        return ($type->allowsNull() ? '?' : '')
            . $type->__toString();
    }
}
