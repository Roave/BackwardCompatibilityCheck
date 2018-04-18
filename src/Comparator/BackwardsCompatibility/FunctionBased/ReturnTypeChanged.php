<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Formatter\ReflectionFunctionAbstractName;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionType;

/**
 * Verifies if the return type of a function changed at all
 *
 * This is useful when comparing methods of interfaces and non-final classes, where child classes may be affected.
 */
final class ReturnTypeChanged implements FunctionBased
{
    /** @var ReflectionFunctionAbstractName */
    private $formatFunction;

    public function __construct()
    {
        $this->formatFunction = new ReflectionFunctionAbstractName();
    }

    public function compare(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction) : Changes
    {
        $fromReturnType = $this->typeToString($fromFunction->getReturnType());
        $toReturnType   = $this->typeToString($toFunction->getReturnType());

        if ($fromReturnType === $toReturnType) {
            return Changes::new();
        }

        return Changes::fromArray([
            Change::changed(
                sprintf(
                    'The return type of %s changed from %s to %s',
                    $this->formatFunction->__invoke($fromFunction),
                    $fromReturnType,
                    $toReturnType
                ),
                true
            ),
        ]);
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
