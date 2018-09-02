<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\ReflectionFunctionAbstractName;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType;
use function array_intersect_key;
use function array_values;
use function sprintf;

/**
 * Detects a change in a parameter type
 *
 * This is mostly useful for methods, where a change in a parameter type is not allowed in
 * inheritance/interface scenarios.
 */
final class ParameterTypeChanged implements FunctionBased
{
    /** @var ReflectionFunctionAbstractName */
    private $formatFunction;

    public function __construct()
    {
        $this->formatFunction = new ReflectionFunctionAbstractName();
    }

    public function __invoke(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction) : Changes
    {
        /** @var ReflectionParameter[] $fromParameters */
        $fromParameters = array_values($fromFunction->getParameters());
        /** @var ReflectionParameter[] $toParameters */
        $toParameters = array_values($toFunction->getParameters());

        return Changes::fromIterator((function () use ($fromParameters, $toParameters) {
            foreach (array_intersect_key($fromParameters, $toParameters) as $parameterIndex => $commonParameter) {
                yield from $this->compareParameter($commonParameter, $toParameters[$parameterIndex]);
            }
        })());
    }

    private function compareParameter(ReflectionParameter $fromParameter, ReflectionParameter $toParameter) : Changes
    {
        $fromType = $this->typeToString($fromParameter->getType());
        $toType   = $this->typeToString($toParameter->getType());

        if ($fromType === $toType) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            sprintf(
                'The parameter $%s of %s changed from %s to %s',
                $fromParameter->getName(),
                $this->formatFunction->__invoke($fromParameter->getDeclaringFunction()),
                $fromType,
                $toType
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
