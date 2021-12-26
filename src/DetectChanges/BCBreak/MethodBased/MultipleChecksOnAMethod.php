<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\SymbolStartColumn;
use Roave\BetterReflection\Reflection\ReflectionMethod;

final class MultipleChecksOnAMethod implements MethodBased
{
    /** @var MethodBased[] */
    private array $checks;

    public function __construct(MethodBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function __invoke(ReflectionMethod $fromMethod, ReflectionMethod $toMethod): Changes
    {
        return Changes::fromIterator($this->multipleChecks($fromMethod, $toMethod));
    }

    /** @return iterable<int, Change> */
    private function multipleChecks(ReflectionMethod $fromMethod, ReflectionMethod $toMethod): iterable
    {
        $toFile   = $toMethod->getFileName();
        $toLine   = $toMethod->getStartLine();
        $toColumn = SymbolStartColumn::get($toMethod);

        foreach ($this->checks as $check) {
            foreach ($check($fromMethod, $toMethod) as $change) {
                // Note: this approach allows us to quickly add file/line/column to each change, but in future,
                //       we will need to push this concern into each checker instead.
                yield $change->withFilePositionsIfNotAlreadySet($toFile, $toLine, $toColumn);
            }
        }
    }
}
