<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\InterfaceBased;

use Roave\ApiCompare\Changes;
use Roave\ApiCompare\DetectChanges\BCBreak\ClassBased\ClassBased;
use Roave\BetterReflection\Reflection\ReflectionClass;

final class UseClassBasedChecksOnAnInterface implements InterfaceBased
{
    /** @var ClassBased */
    private $check;

    public function __construct(ClassBased $check)
    {
        $this->check = $check;
    }

    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        return $this->check->__invoke($fromClass, $toClass);
    }
}
