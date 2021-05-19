<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Psl\Regex;

/**
 * Methods marked "internal" (docblock) are not affected by BC checks.
 */
final class ExcludeInternalMethod implements MethodBased
{
    private MethodBased $check;

    public function __construct(MethodBased $check)
    {
        $this->check = $check;
    }

    public function __invoke(ReflectionMethod $fromMethod, ReflectionMethod $toMethod): Changes
    {
        if ($this->isInternalDocComment($fromMethod->getDocComment())) {
            return Changes::empty();
        }

        return $this->check->__invoke($fromMethod, $toMethod);
    }

    private function isInternalDocComment(string $comment): bool
    {
        return Regex\matches($comment, '/\s+@internal\s+/');
    }
}
