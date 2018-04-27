<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\ClassBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function array_reduce;

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
        return array_reduce(
            $this->checks,
            function (Changes $changes, ClassBased $check) use ($fromClass, $toClass) : Changes {
                return $changes->mergeWith($check->__invoke($fromClass, $toClass));
            },
            Changes::new()
        );
    }
}
