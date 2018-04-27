<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\TraitBased;

use Roave\ApiCompare\Changes;
use Roave\ApiCompare\DetectChanges\BCBreak\ClassBased\ClassBased;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class UseClassBasedChecksOnATrait implements TraitBased
{
    /** @var ClassBased */
    private $check;

    public function __construct(ClassBased $classBased)
    {
        $this->check = $classBased;
    }

    public function __invoke(ReflectionClass $fromTrait, ReflectionClass $toTrait) : Changes
    {
        return $this->check->__invoke($fromTrait, $toTrait);
    }
}
