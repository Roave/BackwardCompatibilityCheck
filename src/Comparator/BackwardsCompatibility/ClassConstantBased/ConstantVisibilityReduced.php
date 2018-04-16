<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassConstantBased;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

final class ConstantVisibilityReduced implements ConstantBased
{
    private const VISIBILITY_PRIVATE = 'private';

    private const VISIBILITY_PROTECTED = 'protected';

    private const VISIBILITY_PUBLIC = 'public';

    public function compare(ReflectionClassConstant $fromConstant, ReflectionClassConstant $toConstant) : Changes
    {
        $visibilityFrom = $this->propertyVisibility($fromConstant);
        $visibilityTo   = $this->propertyVisibility($toConstant);

        if ($visibilityFrom <= $visibilityTo) {
            return Changes::new();
        }

        return Changes::fromArray([Change::changed(
            sprintf(
                'Constant %s::%s visibility reduced from %s to %s',
                $fromConstant->getDeclaringClass()->getName(),
                $fromConstant->getName(),
                $visibilityFrom,
                $visibilityTo
            ),
            true
        )]);
    }

    private function propertyVisibility(ReflectionClassConstant $property) : string
    {
        if ($property->isPublic()) {
            return self::VISIBILITY_PUBLIC;
        }

        if ($property->isProtected()) {
            return self::VISIBILITY_PROTECTED;
        }

        return self::VISIBILITY_PRIVATE;
    }
}
