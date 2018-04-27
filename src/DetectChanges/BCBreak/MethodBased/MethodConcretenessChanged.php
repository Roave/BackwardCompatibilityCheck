<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\MethodBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use function sprintf;

/**
 * A method that changes from concrete to abstract forces all child class
 * implementations to implement it, and is therefore a BC break
 */
final class MethodConcretenessChanged implements MethodBased
{
    public function __invoke(ReflectionMethod $fromMethod, ReflectionMethod $toMethod) : Changes
    {
        if ($fromMethod->isAbstract() || ! $toMethod->isAbstract()) {
            return Changes::new();
        }

        return Changes::fromArray([Change::changed(
            sprintf(
                'Method %s() of class %s changed from concrete to abstract',
                $fromMethod->getName(),
                $fromMethod->getDeclaringClass()->getName()
            ),
            true
        ),
        ]);
    }
}
