<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\TraitBased;

use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\ClassBased;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class UseClassBasedChecksOnATrait implements TraitBased
{
    /** @var ClassBased */
    private $check;

    public function __construct(ClassBased $classBased)
    {
        $this->check = $classBased;
    }

    public function compare(ReflectionClass $fromTrait, ReflectionClass $toTrait) : Changes
    {
        return $this->check->compare($fromTrait, $toTrait);
    }
}
