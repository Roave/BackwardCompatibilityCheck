<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\SymbolStart;
use Roave\BetterReflection\Reflection\ReflectionProperty;

final class MultipleChecksOnAProperty implements PropertyBased
{
    /** @var PropertyBased[] */
    private array $checks;

    public function __construct(PropertyBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function __invoke(ReflectionProperty $fromProperty, ReflectionProperty $toProperty): Changes
    {
        return Changes::fromIterator($this->multipleChecks($fromProperty, $toProperty));
    }

    /** @return iterable<int, Change> */
    private function multipleChecks(ReflectionProperty $fromProperty, ReflectionProperty $toProperty): iterable
    {
        $toLine   = SymbolStart::getLine($toProperty);
        $toColumn = SymbolStart::getColumn($toProperty);
        $toFile   = $toProperty->getImplementingClass()
            ->getFileName();

        foreach ($this->checks as $check) {
            foreach ($check($fromProperty, $toProperty) as $change) {
                // Note: this approach allows us to quickly add file/line/column to each change, but in future,
                //       we will need to push this concern into each checker instead.
                yield $change->withFilePositionsIfNotAlreadySet($toFile, $toLine, $toColumn);
            }
        }
    }
}
