<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function array_reduce;

final class MultipleChecksOnATrait implements TraitBased
{
    /** @var TraitBased[] */
    private $checks;

    public function __construct(TraitBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function __invoke(ReflectionClass $fromTrait, ReflectionClass $toTrait) : Changes
    {
        return array_reduce(
            $this->checks,
            function (Changes $changes, TraitBased $check) use ($fromTrait, $toTrait) : Changes {
                return $changes->mergeWith($check->__invoke($fromTrait, $toTrait));
            },
            Changes::empty()
        );
    }
}
