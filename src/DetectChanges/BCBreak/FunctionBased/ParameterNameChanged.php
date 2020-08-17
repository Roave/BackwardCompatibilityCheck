<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\ReflectionFunctionAbstractName;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionParameter;

use function array_intersect_key;
use function Safe\sprintf;

/**
 * @todo only apply this to PHP 8+ code
 *
 * Detects a change in a parameter type
 *
 * This is mostly useful for methods, where a change in a parameter name is not allowed in
 * inheritance/interface scenarios, except if annotated with `no-named-arguments`
 */
final class ParameterNameChanged implements FunctionBased
{
    private ReflectionFunctionAbstractName $formatFunction;

    public function __construct()
    {
        $this->formatFunction = new ReflectionFunctionAbstractName();
    }

    public function __invoke(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction): Changes
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
    private function checkSymbols(array $from, array $to): iterable
    {
        foreach (array_intersect_key($from, $to) as $index => $commonParameter) {
            yield from $this->compareParameter($commonParameter, $to[$index]);
        }
    }

    /**
     * @return iterable|Change[]
     */
    private function compareParameter(ReflectionParameter $fromParameter, ReflectionParameter $toParameter): iterable
    {
        // @todo detect if the method has a @no-named-arguments annotation, and return if so

        $fromName = $fromParameter->getName();
        $toName   = $toParameter->getName();

        if ($fromName === $toName) {
            return;
        }

        yield Change::changed(
            sprintf(
                'Parameter %d of %s changed name from %s to %s',
                $fromParameter->getPosition(),
                $this->formatFunction->__invoke($fromParameter->getDeclaringFunction()),
                $fromName,
                $toName
            ),
            true
        );
    }
}
