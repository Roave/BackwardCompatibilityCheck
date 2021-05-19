<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased;

use Psl\Regex;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

/**
 * Traits marked "internal" (docblock) are not affected by BC checks.
 */
final class ExcludeInternalTrait implements TraitBased
{
    private TraitBased $check;

    public function __construct(TraitBased $check)
    {
        $this->check = $check;
    }

    public function __invoke(ReflectionClass $fromTrait, ReflectionClass $toTrait): Changes
    {
        if ($this->isInternalDocComment($fromTrait->getDocComment())) {
            return Changes::empty();
        }

        return $this->check->__invoke($fromTrait, $toTrait);
    }

    private function isInternalDocComment(string $comment): bool
    {
        return Regex\matches($comment, '/\s+@internal\s+/');
    }
}
