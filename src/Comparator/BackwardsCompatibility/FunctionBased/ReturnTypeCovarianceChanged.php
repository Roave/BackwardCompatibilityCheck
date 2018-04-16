<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator\Variance\TypeIsCovariant;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionType;

/**
 * When the return type of a function changes, the new return type must be covariant to the current type.
 *
 * If that's not the case, then consumers of the API will be presented with values that they cannot work with.
 */
final class ReturnTypeCovarianceChanged implements FunctionBased
{
    /** @var TypeIsCovariant */
    private $typeIsCovariant;

    public function __construct(TypeIsCovariant $typeIsCovariant)
    {
        $this->typeIsCovariant = $typeIsCovariant;
    }

    public function compare(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction) : Changes
    {
        $fromReturnType = $fromFunction->getReturnType();
        $toReturnType   = $toFunction->getReturnType();

        if ($this->typeIsCovariant->__invoke($fromReturnType, $toReturnType)) {
            return Changes::new();
        }

        return Changes::fromArray([
            Change::changed(
                sprintf(
                    'The return type of %s changed from %s to the non-covariant %s',
                    $this->functionOrMethodName($fromFunction),
                    $this->typeToString($fromReturnType),
                    $this->typeToString($toReturnType)
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
