<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassConstantBased;

use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

final class OnlyProtectedClassConstantChanged implements ClassConstantBased
{
    /**
     * @var ClassConstantBased
     */
    private $constantCheck;

    public function __construct(ClassConstantBased $constantCheck)
    {
        $this->constantCheck = $constantCheck;
    }

    public function compare(ReflectionClassConstant $fromConstant, ReflectionClassConstant $toConstant) : Changes
    {
        if (! $fromConstant->isProtected()) {
            return Changes::new();
        }

        return $this->constantCheck->compare($fromConstant, $toConstant);
    }
}
