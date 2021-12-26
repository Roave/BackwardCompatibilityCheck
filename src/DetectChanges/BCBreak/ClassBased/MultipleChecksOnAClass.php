<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\SymbolStartColumn;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class MultipleChecksOnAClass implements ClassBased
{
    /** @var ClassBased[] */
    private array $checks;

    public function __construct(ClassBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass): Changes
    {
        return Changes::fromIterator($this->multipleChecks($fromClass, $toClass));
    }

    /** @return iterable<int, Change> */
    private function multipleChecks(ReflectionClass $fromClass, ReflectionClass $toClass): iterable
    {
        $toFile   = $toClass->getFileName();
        $toLine   = $toClass->getStartLine();
        $toColumn = SymbolStartColumn::get($toClass);

        foreach ($this->checks as $check) {
            foreach ($check($fromClass, $toClass) as $change) {
                // Note: this approach allows us to quickly add file/line/column to each change, but in future,
                //       we will need to push this concern into each checker instead.
                yield $change
                    ->onFile($toFile)
                    ->onLine($toLine)
                    ->onColumn($toColumn);
            }
        }
    }
}
