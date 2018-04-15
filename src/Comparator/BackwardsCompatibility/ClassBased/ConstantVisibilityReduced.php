<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use Assert\Assert;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

final class ConstantVisibilityReduced implements ClassBased
{
    private const VISIBILITY_PRIVATE = 'private';

    private const VISIBILITY_PROTECTED = 'protected';

    private const VISIBILITY_PUBLIC = 'public';

    public function compare(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        Assert::that($fromClass->getName())->same($toClass->getName());

        $visibilitiesFrom = $this->constantVisibilities($fromClass);
        $visibilitiesTo   = $this->constantVisibilities($toClass);

        $affectedVisibilities = array_filter(
            array_combine(
                array_keys(array_intersect_key($visibilitiesFrom, $visibilitiesTo)),
                array_map(
                    function (string $visibilityFrom, string $visibilityTo) : array {
                        return [$visibilityFrom, $visibilityTo];
                    },
                    array_intersect_key($visibilitiesFrom, $visibilitiesTo),
                    array_intersect_key($visibilitiesTo, $visibilitiesFrom)
                )
            ),
            function (array $visibilities) : bool {
                // Note: works because public, protected and private are (luckily) sortable
                return $visibilities[0] > $visibilities[1];
            }
        );

        return Changes::fromArray(array_values(array_map(function (string $propertyName, array $visibilities) use (
            $fromClass
        ) : Change {
            return Change::changed(
                sprintf(
                    'Constant %s::%s changed visibility from %s to %s',
                    $fromClass->getName(),
                    $propertyName,
                    $visibilities[0],
                    $visibilities[1]
                ),
                true
            );
        }, array_keys($affectedVisibilities), $affectedVisibilities)));
    }

    /** @return string[] indexed by constant name */
    private function constantVisibilities(ReflectionClass $class) : array
    {
        return array_map(function (ReflectionClassConstant $constant) : string {
            if ($constant->isPublic()) {
                return self::VISIBILITY_PUBLIC;
            }

            if ($constant->isProtected()) {
                return self::VISIBILITY_PROTECTED;
            }

            return self::VISIBILITY_PRIVATE;
        }, $class->getReflectionConstants());
    }
}