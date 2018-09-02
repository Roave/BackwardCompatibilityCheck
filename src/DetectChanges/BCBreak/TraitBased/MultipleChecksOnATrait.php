<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class MultipleChecksOnATrait implements TraitBased
{
    /** @var TraitBased[] */
    private $checks;

    public function __construct(TraitBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function __invoke(ReflectionClass $fromTrait, ReflectionClass $toTrait) : Changes
    {
        return Changes::fromIterator((function () use ($fromTrait, $toTrait) : iterable {
            foreach ($this->checks as $check) {
                yield from $check->__invoke($fromTrait, $toTrait);
            }
        })());
    }
}
