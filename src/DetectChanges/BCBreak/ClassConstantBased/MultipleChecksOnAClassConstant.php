<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\SymbolStart;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

final class MultipleChecksOnAClassConstant implements ClassConstantBased
{
    /** @var ClassConstantBased[] */
    private array $checks;

    public function __construct(ClassConstantBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function __invoke(ReflectionClassConstant $fromConstant, ReflectionClassConstant $toConstant): Changes
    {
        return Changes::fromIterator($this->multipleChecks($fromConstant, $toConstant));
    }

    /** @return iterable<int, Change> */
    private function multipleChecks(ReflectionClassConstant $fromConstant, ReflectionClassConstant $toConstant): iterable
    {
        $toLine   = $toConstant->getStartLine();
        $toColumn = SymbolStart::getColumn($toConstant);
        $toFile   = $toConstant->getDeclaringClass()
            ->getFileName();

        foreach ($this->checks as $check) {
            foreach ($check($fromConstant, $toConstant) as $change) {
                // Note: this approach allows us to quickly add file/line/column to each change, but in future,
                //       we will need to push this concern into each checker instead.
                yield $change->withFilePositionsIfNotAlreadySet($toFile, $toLine, $toColumn);
            }
        }
    }
}
