<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\SymbolStartColumn;
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
        $toFile   = $toTrait->getFileName();
        $toLine   = $toTrait->getStartLine();
        $toColumn = SymbolStartColumn::get($toTrait);

        foreach ($this->checks as $check) {
            foreach ($check($fromTrait, $toTrait) as $change) {
                // Note: this approach allows us to quickly add file/line/column to each change, but in future,
                //       we will need to push this concern into each checker instead.
                yield $change->withFilePositionsIfNotAlreadySet($toFile, $toLine, $toColumn);
            }
        }
    }
}
