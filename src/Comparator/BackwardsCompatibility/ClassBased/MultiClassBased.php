<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function array_reduce;

final class MultiClassBased implements ClassBased
{
    /** @var ClassBased[] */
    private $checks;

    public function __construct(ClassBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function compare(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        return array_reduce(
            $this->checks,
            function (Changes $changes, ClassBased $check) use ($fromClass, $toClass) : Changes {
                return $changes->mergeWith($check->compare($fromClass, $toClass));
            },
            Changes::new()
        );
    }
}
