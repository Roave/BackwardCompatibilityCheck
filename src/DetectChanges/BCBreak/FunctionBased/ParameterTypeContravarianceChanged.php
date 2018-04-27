<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\FunctionBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\DetectChanges\Variance\TypeIsContravariant;
use Roave\ApiCompare\Formatter\ReflectionFunctionAbstractName;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType;
use function array_intersect_key;
use function array_values;
use function sprintf;

/**
 * When a parameter type changes, the new type should be wider than the previous type, or else
 * the callers will be passing invalid data to the function.
 */
final class ParameterTypeContravarianceChanged implements FunctionBased
{
    /** @var TypeIsContravariant */
    private $typeIsContravariant;

    /** @var ReflectionFunctionAbstractName */
    private $formatFunction;

    public function __construct(TypeIsContravariant $typeIsContravariant)
    {
        $this->typeIsContravariant = $typeIsContravariant;
        $this->formatFunction      = new ReflectionFunctionAbstractName();
    }

    public function __invoke(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction) : Changes
    {
        /** @var ReflectionParameter[] $fromParameters */
        $fromParameters = array_values($fromFunction->getParameters());
        /** @var ReflectionParameter[] $toParameters */
        $toParameters = array_values($toFunction->getParameters());

        $changes = Changes::empty();

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

    private function typeToString(?ReflectionType $type) : string
    {
        if (! $type) {
            return 'no type';
        }

        return ($type->allowsNull() ? '?' : '')
            . $type->__toString();
    }
}
