<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
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

    /** @return iterable|Change[] */
    private function multipleChecks(ReflectionMethod $fromMethod, ReflectionMethod $toMethod): iterable
    {
        foreach ($this->checks as $check) {
            yield from $check->__invoke($fromMethod, $toMethod);
        }
    }
}
