<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator\Variance\TypeIsContravariant;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType;

/**
 * When a parameter type changes, the new type should be wider than the previous type, or else
 * the callers will be passing invalid data to the function.
 */
final class ParameterTypeContravarianceChanged implements FunctionBased
{
    /** @var TypeIsContravariant */
    private $typeIsContravariant;

    public function __construct(TypeIsContravariant $typeIsContravariant)
    {
        $this->typeIsContravariant = $typeIsContravariant;
    }

    public function compare(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction) : Changes
    {
        /** @var ReflectionParameter[] $fromParameters */
        $fromParameters = array_values($fromFunction->getParameters());
        /** @var ReflectionParameter[] $toParameters */
        $toParameters = array_values($toFunction->getParameters());

        $changes = Changes::new();

        foreach (array_intersect_key($fromParameters, $toParameters) as $parameterIndex => $commonParameter) {
            $changes = $changes->mergeWith($this->compareParameter($commonParameter, $toParameters[$parameterIndex]));
        }

        return $changes;
    }

    private function compareParameter(ReflectionParameter $fromParameter, ReflectionParameter $toParameter) : Changes
    {
        $fromType = $fromParameter->getType();
        $toType   = $toParameter->getType();

        if ($this->typeIsContravariant->__invoke($fromType, $toType)) {
            return Changes::new();
        }

        return Changes::fromArray([
            Change::changed(
                sprintf(
                    'The parameter $%s of %s() changed from %s to a non-contravariant %s',
                    $fromParameter->getName(),
                    $this->functionOrMethodName($fromParameter->getDeclaringFunction()),
                    $this->typeToString($fromType),
                    $this->typeToString($toType)
                ),
                true
            ),
        ]);
    }

    private function functionOrMethodName(ReflectionFunctionAbstract $function) : string
    {
        if ($function instanceof ReflectionMethod) {
            return $function->getDeclaringClass()->getName()
                . ($function->isStatic() ? '::' : '#')
                . $function->getName();
        }

        return $function->getName();
    }

    private function typeToString(?ReflectionType $type) : string
    {
        if (! $type) {
            return 'no type';
        }

        return ($type->allowsNull() ? '?' : '')
            . $type->__toString();
    }
}
