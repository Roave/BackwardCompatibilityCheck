<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Psl\Dict;
use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\FunctionName;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionType;

/**
 * Detects a change in a parameter type
 *
 * This is mostly useful for methods, where a change in a parameter type is not allowed in
 * inheritance/interface scenarios.
 */
final class ParameterTypeChanged implements FunctionBased
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
        return Changes::fromIterator($this->checkSymbols(
            $fromFunction->getParameters(),
            $toFunction->getParameters()
        ));
    }

    /**
     * @param list<ReflectionParameter> $from
     * @param list<ReflectionParameter> $to
     *
     * @return iterable<int, Change>
     */
    private function checkSymbols(array $from, array $to): iterable
    {
        foreach (Dict\intersect_by_key($from, $to) as $index => $commonParameter) {
            yield from $this->compareParameter($commonParameter, $to[$index]);
        }
    }

    /**
     * @return iterable<int, Change>
     */
    private function compareParameter(ReflectionParameter $fromParameter, ReflectionParameter $toParameter): iterable
    {
        $fromType = $this->typeToString($fromParameter->getType());
        $toType   = $this->typeToString($toParameter->getType());

        if ($fromType === $toType) {
            return;
        }

        yield Change::changed(
            Str\format(
                'The parameter $%s of %s changed from %s to %s',
                $fromParameter->getName(),
                ($this->formatFunction)($fromParameter->getDeclaringFunction()),
                $fromType,
                $toType
            )
        );
    }

    private function typeToString(?ReflectionType $type): string
    {
        if (! $type) {
            return 'no type';
        }

        return $type->__toString();
    }
}
