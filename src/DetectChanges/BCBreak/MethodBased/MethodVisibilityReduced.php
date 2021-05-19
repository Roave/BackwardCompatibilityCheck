<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Psl\Str;

final class MethodVisibilityReduced implements MethodBased
{
    private const VISIBILITY_PRIVATE = 'private';

    private const VISIBILITY_PROTECTED = 'protected';

    private const VISIBILITY_PUBLIC = 'public';

    public function __invoke(ReflectionMethod $fromMethod, ReflectionMethod $toMethod): Changes
    {
        $visibilityFrom = $this->methodVisibility($fromMethod);
        $visibilityTo   = $this->methodVisibility($toMethod);

        // Works because private, protected and public are sortable:
        if ($visibilityFrom <= $visibilityTo) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'Method %s() of class %s visibility reduced from %s to %s',
                $fromMethod->getName(),
                $fromMethod->getDeclaringClass()->getName(),
                $visibilityFrom,
                $visibilityTo
            ),
            true
        ));
    }

    private function methodVisibility(ReflectionMethod $method): string
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
