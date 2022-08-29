<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;

/**
 * A class cannot become abstract without introducing an explicit BC break, since
 * all child classes or implementors need to be changed to implement its abstract API,
 * and all instantiations start to fail.
 */
final class ClassBecameAbstract implements ClassBased
{
    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass): Changes
    {
        if ($fromClass->isInterface() !== $toClass->isInterface()) {
            // checking whether a class became an interface is done in `ClassBecameInterface`
            return Changes::empty();
        }

        if ($fromClass->isAbstract() || ! $toClass->isAbstract()) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format('Class %s became abstract', $fromClass->getName()),
        ));
    }
}
