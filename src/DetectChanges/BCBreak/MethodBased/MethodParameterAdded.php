<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use Psl\Dict;
use Psl\Str;
use Psl\Vec;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;

/**
 * Adding a parameter to a method on a non-final class is a BC break.
 * Any child classes which extend public and protected methods will be incompatible with the new method signature.
 */
final class MethodParameterAdded implements MethodBased
{
    public function __invoke(ReflectionMethod $fromMethod, ReflectionMethod $toMethod): Changes
    {
        if ($fromMethod->getDeclaringClass()->isFinal() || $fromMethod->isPrivate()) {
            return Changes::empty();
        }

        $toParameters = $toMethod->getParameters();
        if ($fromMethod->isConstructor()) {
            // new optional parameters of constructors are not BC breaks,
            // as the method signature of child classes does not need to match the parent
            $toParameters = Vec\filter($toParameters, static fn (ReflectionParameter $param) => ! $param->isOptional());
        }

        $added = Dict\diff(
            Vec\map($toParameters, static fn (ReflectionParameter $param) => $param->getName()),
            Vec\map($fromMethod->getParameters(), static fn (ReflectionParameter $param) => $param->getName()),
        );

        return Changes::fromList(
            ...Vec\map(
                $added,
                static fn (string $paramName): Change => Change::added(
                    Str\format(
                        'Parameter %s was added to Method %s() of class %s',
                        $paramName,
                        $fromMethod->getName(),
                        $fromMethod->getDeclaringClass()->getName(),
                    ),
                ),
            ),
        );
    }
}
