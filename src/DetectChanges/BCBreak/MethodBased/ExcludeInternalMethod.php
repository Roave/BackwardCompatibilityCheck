<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use function Safe\preg_match;

/**
 * Methods marked "internal" (docblock) are not affected by BC checks.
 */
final class ExcludeInternalMethod implements MethodBased
{
    /** @var MethodBased */
    private $check;

    public function __construct(MethodBased $check)
    {
        $this->check = $check;
    }

    public function __invoke(ReflectionMethod $fromMethod, ReflectionMethod $toMethod) : Changes
    {
        if ($this->isInternalDocComment($fromMethod->getDocComment())) {
            return Changes::empty();
        }

        return $this->check->__invoke($fromMethod, $toMethod);
    }

    private function isInternalDocComment(string $comment) : bool
    {
        return preg_match('/\s+@internal\s+/', $comment) === 1;
    }
}
