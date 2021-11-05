<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class MultipleChecksOnATrait implements TraitBased
{
    /** @var TraitBased[] */
    private array $checks;

    public function __construct(TraitBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function __invoke(ReflectionClass $fromTrait, ReflectionClass $toTrait): Changes
    {
        return Changes::fromIterator($this->multipleChecks($fromTrait, $toTrait));
    }

    /** @return iterable<int, Change> */
    private function multipleChecks(ReflectionClass $fromTrait, ReflectionClass $toTrait): iterable
    {
        foreach ($this->checks as $check) {
            yield from $check($fromTrait, $toTrait);
        }
    }
}
