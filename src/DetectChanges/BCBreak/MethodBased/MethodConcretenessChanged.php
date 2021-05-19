<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionMethod;

/**
 * A method that changes from concrete to abstract forces all child class
 * implementations to implement it, and is therefore a BC break
 */
final class MethodConcretenessChanged implements MethodBased
{
    public function __invoke(ReflectionMethod $fromMethod, ReflectionMethod $toMethod): Changes
    {
        if ($fromMethod->isAbstract() || ! $toMethod->isAbstract()) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'Method %s() of class %s changed from concrete to abstract',
                $fromMethod->getName(),
                $fromMethod->getDeclaringClass()->getName()
            ),
            true
        ));
    }
}
