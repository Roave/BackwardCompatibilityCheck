<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased;

use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

use function var_export;

final class ClassConstantValueChanged implements ClassConstantBased
{
    public function __invoke(ReflectionClassConstant $fromConstant, ReflectionClassConstant $toConstant): Changes
    {
        if ($fromConstant->isPrivate()) {
            return Changes::empty();
        }

        /** @psalm-suppress MixedAssignment */
        $fromValue = $fromConstant->getValue();
        /** @psalm-suppress MixedAssignment */
        $toValue = $toConstant->getValue();

        if ($fromValue === $toValue) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'Value of constant %s::%s changed from %s to %s',
                $fromConstant->getDeclaringClass()->getName(),
                $fromConstant->getName(),
                var_export($fromValue, true),
                var_export($toValue, true),
            ),
        ));
    }
}
