<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Psl\Regex;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

/**
 * Classes marked "internal" (docblock) are not affected by BC checks.
 */
final class ExcludeInternalClass implements ClassBased
{
    private ClassBased $check;

    public function __construct(ClassBased $check)
    {
        $this->check = $check;
    }

    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass): Changes
    {
        if ($this->isInternalDocComment($fromClass->getDocComment())) {
            return Changes::empty();
        }

        return $this->check->__invoke($fromClass, $toClass);
    }

    private function isInternalDocComment(string $comment): bool
    {
        return Regex\matches($comment, '/\s+@internal\s+/');
    }
}
