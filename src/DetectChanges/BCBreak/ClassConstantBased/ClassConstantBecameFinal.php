<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased;

use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

/**
 * A constant that changes from non-final to final breaks all child classes
 * (and interface implementors) that override it.
 */
final class ClassConstantBecameFinal implements ClassConstantBased
{
    public function __invoke(ReflectionClassConstant $fromConstant, ReflectionClassConstant $toConstant): Changes
    {
        if ($fromConstant->isFinal() || ! $toConstant->isFinal()) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'Constant %s::%s became final',
                $fromConstant->getDeclaringClass()->getName(),
                $fromConstant->getName(),
            ),
        ));
    }
}
