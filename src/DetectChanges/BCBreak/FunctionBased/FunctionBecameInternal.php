<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Psl\Regex;
use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\FunctionName;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;

/**
 * A function that is marked internal is no available to downstream consumers.
 */
final class FunctionBecameInternal implements FunctionBased
{
    private FunctionName $formatFunction;

    public function __construct()
    {
        $this->formatFunction = new FunctionName();
    }

    public function __invoke(
        ReflectionMethod|ReflectionFunction $fromFunction,
        ReflectionMethod|ReflectionFunction $toFunction,
    ): Changes {
        if (
            $this->isInternalDocComment($toFunction->getDocComment())
            && ! $this->isInternalDocComment($fromFunction->getDocComment())
        ) {
            return Changes::fromList(Change::changed(
                Str\format(
                    '%s was marked "@internal"',
                    ($this->formatFunction)($fromFunction),
                ),
            ));
        }

        return Changes::empty();
    }

    private function isInternalDocComment(string $comment): bool
    {
        return Regex\matches($comment, '/\s+@internal\s+/');
    }
}
