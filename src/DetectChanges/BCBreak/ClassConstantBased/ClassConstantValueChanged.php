<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use function Safe\sprintf;
use function var_export;

final class ClassConstantValueChanged implements ClassConstantBased
{
    public function __invoke(ReflectionClassConstant $fromConstant, ReflectionClassConstant $toConstant) : Changes
    {
        if ($fromConstant->isPrivate()) {
            return Changes::empty();
        }

        $fromValue = $fromConstant->getValue();
        $toValue   = $toConstant->getValue();

        if ($fromValue === $toValue) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            sprintf(
                'Value of constant %s::%s changed from %s to %s',
                $fromConstant->getDeclaringClass()->getName(),
                $fromConstant->getName(),
                var_export($fromValue, true),
                var_export($toValue, true)
            ),
            true
        ));
    }
}
