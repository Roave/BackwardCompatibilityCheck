<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Psl\Str;
use Psl\Type;
use ReflectionProperty;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsCovariant;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeWithReflectorScope;
use Roave\BackwardCompatibility\Formatter\ReflectionFunctionAbstractName;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * When the return type of a function changes, the new return type must be covariant to the current type.
 *
 * If that's not the case, then consumers of the API will be presented with values that they cannot work with.
 */
final class ReturnTypeCovarianceChanged implements FunctionBased
{
    private TypeIsCovariant $typeIsCovariant;

    private ReflectionFunctionAbstractName $formatFunction;

    public function __construct(TypeIsCovariant $typeIsCovariant)
    {
        $this->typeIsCovariant = $typeIsCovariant;
        $this->formatFunction  = new ReflectionFunctionAbstractName();
    }

    public function __invoke(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction): Changes
    {
        $fromReturnType = $fromFunction->getReturnType();
        $toReturnType   = $toFunction->getReturnType();

        if (
            ($this->typeIsCovariant)(
                new TypeWithReflectorScope($fromReturnType, $this->extractReflector($fromFunction)),
                new TypeWithReflectorScope($toReturnType, $this->extractReflector($toFunction)),
            )
        ) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'The return type of %s changed from %s to the non-covariant %s',
                ($this->formatFunction)($fromFunction),
                $this->typeToString($fromReturnType),
                $this->typeToString($toReturnType)
            ),
            true
        ));
    }

    private function typeToString(?ReflectionType $type): string
    {
        if (! $type) {
            return 'no type';
        }

        return $type->__toString();
    }

    /** @TODO may the gods of BC compliance be merciful on me */
    private function extractReflector(ReflectionFunctionAbstract $function): Reflector
    {
        $reflectionReflector = new ReflectionProperty(ReflectionFunctionAbstract::class, 'reflector');

        $reflectionReflector->setAccessible(true);

        return Type\object(Reflector::class)
            ->coerce($reflectionReflector->getValue($function));
    }
}
