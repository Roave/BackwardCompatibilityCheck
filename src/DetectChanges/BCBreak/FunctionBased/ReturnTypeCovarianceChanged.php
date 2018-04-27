<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\FunctionBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\DetectChanges\Variance\TypeIsCovariant;
use Roave\ApiCompare\Formatter\ReflectionFunctionAbstractName;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionType;
use function sprintf;

/**
 * When the return type of a function changes, the new return type must be covariant to the current type.
 *
 * If that's not the case, then consumers of the API will be presented with values that they cannot work with.
 */
final class ReturnTypeCovarianceChanged implements FunctionBased
{
    /** @var TypeIsCovariant */
    private $typeIsCovariant;

    /** @var ReflectionFunctionAbstractName */
    private $formatFunction;

    public function __construct(TypeIsCovariant $typeIsCovariant)
    {
        $this->typeIsCovariant = $typeIsCovariant;
        $this->formatFunction  = new ReflectionFunctionAbstractName();
    }

    public function __invoke(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction) : Changes
    {
        $fromReturnType = $fromFunction->getReturnType();
        $toReturnType   = $toFunction->getReturnType();

        if ($this->typeIsCovariant->__invoke($fromReturnType, $toReturnType)) {
            return Changes::empty();
        }

        return Changes::fromArray([
            Change::changed(
                sprintf(
                    'The return type of %s changed from %s to the non-covariant %s',
                    $this->formatFunction->__invoke($fromFunction),
                    $this->typeToString($fromReturnType),
                    $this->typeToString($toReturnType)
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
