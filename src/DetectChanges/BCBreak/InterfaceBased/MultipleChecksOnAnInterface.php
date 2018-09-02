<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class MultipleChecksOnAnInterface implements InterfaceBased
{
    /** @var InterfaceBased[] */
    private $checks;

    public function __construct(InterfaceBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function __invoke(ReflectionClass $fromInterface, ReflectionClass $toInterface) : Changes
    {
        return Changes::fromIterator((function () use ($fromInterface, $toInterface) : iterable {
            foreach ($this->checks as $check) {
                yield from $check->__invoke($fromInterface, $toInterface);
            }
        })());
    }
}
