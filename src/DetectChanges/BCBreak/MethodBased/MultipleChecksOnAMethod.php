<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\MethodBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use function array_reduce;

final class MultipleChecksOnAMethod implements MethodBased
{
    /** @var MethodBased[] */
    private $checks;

    public function __construct(MethodBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function __invoke(ReflectionMethod $fromMethod, ReflectionMethod $toMethod) : Changes
    {
        return array_reduce(
            $this->checks,
            function (Changes $changes, MethodBased $check) use ($fromMethod, $toMethod) : Changes {
                return $changes->mergeWith($check->__invoke($fromMethod, $toMethod));
            },
            Changes::new()
        );
    }
}
