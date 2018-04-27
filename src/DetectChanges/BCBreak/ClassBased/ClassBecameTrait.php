<?php

declare(strict_types=1);

namespace Roave\ApiCompare\DetectChanges\BCBreak\ClassBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function sprintf;

/**
 * A class cannot become a trait without introducing an explicit BC break, since
 * all child classes or implementors need to be changed from `extends` to `use`,
 * and all instantiations start failing
 */
final class ClassBecameTrait implements ClassBased
{
    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        if ($fromClass->isTrait() || ! $toClass->isTrait()) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            sprintf('Class %s became a trait', $fromClass->getName()),
            true
        ));
    }
}
