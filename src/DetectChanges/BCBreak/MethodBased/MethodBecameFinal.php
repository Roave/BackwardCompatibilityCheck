<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use function sprintf;

/**
 * A method that changes from non-final to final breaks all child classes that
 * override it.
 */
final class MethodBecameFinal implements MethodBased
{
    public function __invoke(ReflectionMethod $fromMethod, ReflectionMethod $toMethod) : Changes
    {
        if ($fromMethod->isFinal() || ! $toMethod->isFinal()) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            sprintf(
                'Method %s() of class %s became final',
                $fromMethod->getName(),
                $fromMethod->getDeclaringClass()->getName()
            ),
            true
        ));
    }
}
