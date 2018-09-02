<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class MultipleChecksOnAClass implements ClassBased
{
    /** @var ClassBased[] */
    private $checks;

    public function __construct(ClassBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        return Changes::fromIterator((function () use ($fromClass, $toClass) {
            foreach ($this->checks as $check) {
                yield from $check->__invoke($fromClass, $toClass);
            }
        })());
    }
}
