<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\InterfaceBased;

use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\ClassBased;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class UseClassBasedChecksOnAnInterface implements InterfaceBased
{
    /** @var ClassBased */
    private $check;

    public function __construct(ClassBased $check)
    {
        $this->check = $check;
    }

    public function compare(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        return $this->check->compare($fromClass, $toClass);
    }
}
