<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\MethodBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use function sprintf;

/**
 * A method that changes from instance to static or the opposite has to be called differently,
 * so any of such changes are to be considered BC breaks
 */
final class MethodScopeChanged implements MethodBased
{
    public function compare(ReflectionMethod $fromMethod, ReflectionMethod $toMethod) : Changes
    {
        $scopeFrom = $this->methodScope($fromMethod);
        $scopeTo   = $this->methodScope($toMethod);

        if ($scopeFrom === $scopeTo) {
            return Changes::new();
        }

        return Changes::fromArray([Change::changed(
            sprintf(
                'Method %s() of class %s changed scope from %s to %s',
                $fromMethod->getName(),
                $fromMethod->getDeclaringClass()->getName(),
                $scopeFrom,
                $scopeTo
            ),
            true
        ),
        ]);
    }

    private function methodScope(ReflectionMethod $method) : string
    {
        return $method->isStatic() ? 'static' : 'instance';
    }
}
