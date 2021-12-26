<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\SymbolStartColumn;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class MultipleChecksOnAnInterface implements InterfaceBased
{
    /** @var InterfaceBased[] */
    private array $checks;

    public function __construct(InterfaceBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function __invoke(ReflectionClass $fromInterface, ReflectionClass $toInterface): Changes
    {
        return Changes::fromIterator($this->multipleChecks($fromInterface, $toInterface));
    }

    /** @return iterable<int, Change> */
    private function multipleChecks(ReflectionClass $fromInterface, ReflectionClass $toInterface): iterable
    {
        $toFile   = $toInterface->getFileName();
        $toLine   = $toInterface->getStartLine();
        $toColumn = SymbolStartColumn::get($toInterface);

        foreach ($this->checks as $check) {
            foreach ($check($fromInterface, $toInterface) as $change) {
                // Note: this approach allows us to quickly add file/line/column to each change, but in future,
                //       we will need to push this concern into each checker instead.
                yield $change->withFilePositionsIfNotAlreadySet($toFile, $toLine, $toColumn);
            }
        }
    }
}
