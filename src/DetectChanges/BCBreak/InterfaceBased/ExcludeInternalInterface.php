<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use Psl\Regex;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

/**
 * Interfaces marked "internal" (docblock) are not affected by BC checks.
 */
final class ExcludeInternalInterface implements InterfaceBased
{
    public function __construct(private InterfaceBased $check)
    {
    }

    public function __invoke(ReflectionClass $fromInterface, ReflectionClass $toInterface): Changes
    {
        if ($this->isInternalDocComment($fromInterface->getDocComment())) {
            return Changes::empty();
        }

        return ($this->check)($fromInterface, $toInterface);
    }

    private function isInternalDocComment(string $comment): bool
    {
        return Regex\matches($comment, '/\s+@internal\s+/');
    }
}
