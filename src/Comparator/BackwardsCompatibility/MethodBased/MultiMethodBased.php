<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\MethodBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty;

final class MultiMethodBased implements MethodBased
{
    /** @var MethodBased[] */
    private $checks;

    public function __construct(MethodBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function compare(ReflectionMethod $fromMethod, ReflectionMethod $toMethod) : Changes
    {
        return array_reduce(
            $this->checks,
            function (Changes $changes, MethodBased $check) use ($fromMethod, $toMethod) : Changes {
                return $changes->mergeWith($check->compare($fromMethod, $toMethod));
            },
            Changes::new()
        );
    }
}
