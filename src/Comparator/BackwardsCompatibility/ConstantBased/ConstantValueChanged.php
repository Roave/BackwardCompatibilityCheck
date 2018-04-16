<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\ConstantBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

final class ConstantValueChanged implements ConstantBased
{
    public function compare(ReflectionClassConstant $fromConstant, ReflectionClassConstant $toConstant) : Changes
    {
        if ($fromConstant->isPrivate()) {
            return Changes::new();
        }

        $fromValue = $fromConstant->getValue();
        $toValue   = $toConstant->getValue();

        if ($fromValue === $toValue) {
            return Changes::new();
        }

        return Changes::fromArray([
            Change::changed(
                sprintf(
                    'Value of constant %s::%s changed from %s to %s',
                    $fromConstant->getDeclaringClass()->getName(),
                    $fromConstant->getName(),
                    var_export($fromValue, true),
                    var_export($toValue, true)
                ),
                true
            ),
        ]);
    }
}
