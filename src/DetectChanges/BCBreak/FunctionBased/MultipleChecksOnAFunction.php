<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
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
        ReflectionMethod|ReflectionFunction $toFunction
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
        ReflectionMethod|ReflectionFunction $toFunction
    ): iterable {
        foreach ($this->checks as $check) {
            yield from $check($fromFunction, $toFunction);
        }
    }
}
