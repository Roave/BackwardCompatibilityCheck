<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function Safe\preg_match;
use function Safe\sprintf;

/**
 * A class that is marked internal is no available to downstream consumers.
 */
final class ClassBecameInternal implements ClassBased
{
    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        if (! $this->isInternalDocComment($fromClass->getDocComment())
            && $this->isInternalDocComment($toClass->getDocComment())
        ) {
            return Changes::fromList(Change::changed(
                sprintf(
                    '%s was marked "@internal"',
                    $fromClass->getName()
                ),
                true
            ));
        }

        return Changes::empty();
    }

    private function isInternalDocComment(string $comment) : bool
    {
        return preg_match('/\s+@internal\s+/', $comment) === 1;
    }
}
