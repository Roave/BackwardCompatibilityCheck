<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Psl\Dict;
use Psl\Str;
use Psl\Vec;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

final class ConstantRemoved implements ClassBased
{
    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass): Changes
    {
        $removedConstants = Dict\diff_by_key(
            $this->accessibleConstants($fromClass),
            $this->accessibleConstants($toClass),
        );

        return Changes::fromList(...Vec\map($removedConstants, static function (ReflectionClassConstant $constant) use ($fromClass): Change {
            return Change::removed(
                Str\format('Constant %s::%s was removed', $fromClass->getName(), $constant->getName()),
            );
        }));
    }

    /** @return ReflectionClassConstant[] */
    private function accessibleConstants(ReflectionClass $class): array
    {
        $classIsOpen = ! $class->isFinal();

        return Dict\filter($class->getConstants(), static function (ReflectionClassConstant $constant) use ($classIsOpen): bool {
            return $constant->isPublic()
                || ($classIsOpen && $constant->isProtected());
        });
    }
}
