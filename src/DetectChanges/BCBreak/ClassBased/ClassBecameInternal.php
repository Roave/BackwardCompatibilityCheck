<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Psl\Regex;
use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

/**
 * A class that is marked internal is no available to downstream consumers.
 */
final class ClassBecameInternal implements ClassBased
{
    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass): Changes
    {
        if (
            ! $this->isInternalDocComment($fromClass->getDocComment())
            && $this->isInternalDocComment($toClass->getDocComment())
        ) {
            return Changes::fromList(Change::changed(
                Str\format(
                    '%s was marked "@internal"',
                    $fromClass->getName(),
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
