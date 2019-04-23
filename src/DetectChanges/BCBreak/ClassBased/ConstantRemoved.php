<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use function array_diff_key;
use function array_filter;
use function array_map;
use function array_values;
use function Safe\sprintf;

final class ConstantRemoved implements ClassBased
{
    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        $removedConstants = array_diff_key(
            $this->accessibleConstants($fromClass),
            $this->accessibleConstants($toClass)
        );

        return Changes::fromList(...array_values(array_map(static function (ReflectionClassConstant $constant) use ($fromClass) : Change {
            return Change::removed(
                sprintf('Constant %s::%s was removed', $fromClass->getName(), $constant->getName()),
                true
            );
        }, $removedConstants)));
    }

    /** @return ReflectionClassConstant[] */
    private function accessibleConstants(ReflectionClass $class) : array
    {
        return array_filter($class->getReflectionConstants(), static function (ReflectionClassConstant $constant) : bool {
            return $constant->isPublic() || $constant->isProtected();
        });
    }
}
