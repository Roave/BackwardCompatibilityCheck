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
use function Safe\sprintf;

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
        return Changes::fromIterator($this->checkSymbols(
            $fromFunction->getParameters(),
            $toFunction->getParameters()
        ));
    }

    /**
     * @param ReflectionParameter[] $from
     * @param ReflectionParameter[] $to
     *
     * @return iterable|Change[]
     */
    private function checkSymbols(array $from, array $to) : iterable
    {
        foreach (array_intersect_key($from, $to) as $index => $commonParameter) {
            yield from $this->compareParameter($commonParameter, $to[$index]);
        }
    }

    /**
     * @return iterable|Change[]
     */
    private function compareParameter(ReflectionParameter $fromParameter, ReflectionParameter $toParameter) : iterable
    {
        $fromType = $this->typeToString($fromParameter->getType());
        $toType   = $this->typeToString($toParameter->getType());

        if ($fromType !== $toType) {
            yield Change::changed(
                sprintf(
                    'The parameter $%s of %s changed from %s to %s',
                    $fromParameter->getName(),
                    $this->formatFunction->__invoke($fromParameter->getDeclaringFunction()),
                    $fromType,
                    $toType
                ),
                true
            );
        }
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
