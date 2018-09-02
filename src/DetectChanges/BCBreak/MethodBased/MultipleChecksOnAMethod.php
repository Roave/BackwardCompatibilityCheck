<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionMethod;

final class MultipleChecksOnAMethod implements MethodBased
{
    /** @var MethodBased[] */
    private $checks;

    public function __construct(MethodBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function __invoke(ReflectionMethod $fromMethod, ReflectionMethod $toMethod) : Changes
    {
        return Changes::fromIterator((function () use ($fromMethod, $toMethod) : iterable {
            foreach ($this->checks as $check) {
                yield from $check->__invoke($fromMethod, $toMethod);
            }
        })());
    }
}
