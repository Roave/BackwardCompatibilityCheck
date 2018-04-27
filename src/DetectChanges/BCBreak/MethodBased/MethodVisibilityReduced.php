<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\MethodBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use function sprintf;

final class MethodVisibilityReduced implements MethodBased
{
    private const VISIBILITY_PRIVATE = 'private';

    private const VISIBILITY_PROTECTED = 'protected';

    private const VISIBILITY_PUBLIC = 'public';

    public function compare(ReflectionMethod $fromMethod, ReflectionMethod $toMethod) : Changes
    {
        $visibilityFrom = $this->methodVisibility($fromMethod);
        $visibilityTo   = $this->methodVisibility($toMethod);

        // Works because private, protected and public are sortable:
        if ($visibilityFrom <= $visibilityTo) {
            return Changes::new();
        }

        return Changes::fromArray([Change::changed(
            sprintf(
                'Method %s() of class %s visibility reduced from %s to %s',
                $fromMethod->getName(),
                $fromMethod->getDeclaringClass()->getName(),
                $visibilityFrom,
                $visibilityTo
            ),
            true
        ),
        ]);
    }

    private function methodVisibility(ReflectionMethod $method) : string
    {
        if ($method->isPublic()) {
            return self::VISIBILITY_PUBLIC;
        }

        if ($method->isProtected()) {
            return self::VISIBILITY_PROTECTED;
        }

        return self::VISIBILITY_PRIVATE;
    }
}
