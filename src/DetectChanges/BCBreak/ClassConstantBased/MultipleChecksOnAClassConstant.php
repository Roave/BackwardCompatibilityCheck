<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use function array_reduce;

final class MultipleChecksOnAClassConstant implements ClassConstantBased
{
    /** @var ClassConstantBased[] */
    private $checks;

    public function __construct(ClassConstantBased ...$checks)
    {
        $this->checks = $checks;
    }

    public function __invoke(ReflectionClassConstant $fromConstant, ReflectionClassConstant $toConstant) : Changes
    {
        return array_reduce(
            $this->checks,
            function (Changes $changes, ClassConstantBased $check) use ($fromConstant, $toConstant) : Changes {
                return $changes->mergeWith($check->__invoke($fromConstant, $toConstant));
            },
            Changes::empty()
        );
    }
}
