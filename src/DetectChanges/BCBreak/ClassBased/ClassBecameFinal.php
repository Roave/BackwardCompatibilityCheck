<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\ClassBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function sprintf;

final class ClassBecameFinal implements ClassBased
{
    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        if ($fromClass->isFinal()) {
            return Changes::empty();
        }

        if (! $toClass->isFinal()) {
            return Changes::empty();
        }

        return Changes::fromArray([Change::changed(
            sprintf('Class %s became final', $fromClass->getName()),
            true
        ),
        ]);
    }
}
