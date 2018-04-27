<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\TraitBased;

use Roave\ApiCompare\Changes;
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

    public function compare(ReflectionClass $fromTrait, ReflectionClass $toTrait) : Changes
    {
        return array_reduce(
            $this->checks,
            function (Changes $changes, TraitBased $check) use ($fromTrait, $toTrait) : Changes {
                return $changes->mergeWith($check->compare($fromTrait, $toTrait));
            },
            Changes::new()
        );
    }
}
