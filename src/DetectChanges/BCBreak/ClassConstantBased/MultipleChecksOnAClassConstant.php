<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
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
        return Changes::fromIterator($this->multipleChecks($fromConstant, $fromConstant));
    }

    /** @return iterable|Change[] */
    private function multipleChecks(ReflectionClassConstant $fromConstant, ReflectionClassConstant $toConstant): iterable
    {
        foreach ($this->checks as $check) {
            yield from $check->__invoke($fromConstant, $toConstant);
        }
    }
}
