<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\SymbolStart;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;

final class MultipleChecksOnAFunction implements FunctionBased
{
    /** @var FunctionBased[] */
    private array $checks;

    public function __construct(FunctionBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function __invoke(
        ReflectionMethod|ReflectionFunction $fromFunction,
        ReflectionMethod|ReflectionFunction $toFunction,
    ): Changes {
        return Changes::fromIterator($this->multipleChecks($fromFunction, $toFunction));
    }

    /**
     * @param T $fromFunction
     * @param T $toFunction
     *
     * @return iterable<int, Change>
     *
     * @template T of ReflectionMethod|ReflectionFunction
     */
    private function multipleChecks(
        ReflectionMethod|ReflectionFunction $fromFunction,
        ReflectionMethod|ReflectionFunction $toFunction,
    ): iterable {
        $toFile   = $toFunction->getFileName();
        $toLine   = SymbolStart::getLine($toFunction);
        $toColumn = SymbolStart::getColumn($toFunction);

        foreach ($this->checks as $check) {
            foreach ($check($fromFunction, $toFunction) as $change) {
                // Note: this approach allows us to quickly add file/line/column to each change, but in future,
                //       we will need to push this concern into each checker instead.
                yield $change->withFilePositionsIfNotAlreadySet($toFile, $toLine, $toColumn);
            }
        }
    }
}
