<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Psl\Regex;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;

/**
 * Functions marked "internal" (docblock) are not affected by BC checks.
 */
final class ExcludeInternalFunction implements FunctionBased
{
    private FunctionBased $check;

    public function __construct(FunctionBased $check)
    {
        $this->check = $check;
    }

    public function __invoke(
        ReflectionMethod|ReflectionFunction $fromFunction,
        ReflectionMethod|ReflectionFunction $toFunction
    ): Changes {
        if ($this->isInternalDocComment($fromFunction->getDocComment())) {
            return Changes::empty();
        }

        return ($this->check)($fromFunction, $toFunction);
    }

    private function isInternalDocComment(string $comment): bool
    {
        return Regex\matches($comment, '/\s+@internal\s+/');
    }
}
