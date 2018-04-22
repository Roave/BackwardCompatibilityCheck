<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\TraitBased;

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

    public function compare(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        return array_reduce(
            $this->checks,
            function (Changes $changes, TraitBased $check) use ($fromClass, $toClass) : Changes {
                return $changes->mergeWith($check->compare($fromClass, $toClass));
            },
            Changes::new()
        );
    }
}
