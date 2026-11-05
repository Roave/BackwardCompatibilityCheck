<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\Variance;

use Psl\Iter;
use Psl\Str;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionUnionType;
use Traversable;

/**
 * This is a simplistic contravariant type check. A more appropriate approach would be to
 * have a `$type->includes($otherType)` check with actual types represented as value objects,
 * but that is a massive piece of work that should be done by importing an external library
 * instead, if this class no longer suffices.
 */
final class TypeIsContravariant
{
    public function __invoke(
        ReflectionIntersectionType|ReflectionUnionType|ReflectionNamedType|null $type,
        ReflectionIntersectionType|ReflectionUnionType|ReflectionNamedType|null $comparedType,
    ): bool {
        if (
            ($type && $type->__toString() === 'never')
            || ($comparedType && $comparedType->__toString() === 'never')
        ) {
            return false;
        }

        if ($comparedType === null || $comparedType->__toString() === 'mixed') {
            return true;
        }

        if ($type === null) {
            // nothing can be contravariant to `mixed` besides `mixed` itself (handled above)
            return false;
        }

        if ($type instanceof ReflectionUnionType) {
            return Iter\all(
                $type->getTypes(),
                fn (ReflectionNamedType|ReflectionIntersectionType $type): bool => $this($type, $comparedType),
            );
        }

        if ($comparedType instanceof ReflectionUnionType) {
            return Iter\any(
                $comparedType->getTypes(),
                fn (ReflectionNamedType|ReflectionIntersectionType $comparedType): bool => $this($type, $comparedType),
            );
        }

        if ($comparedType instanceof ReflectionIntersectionType) {
            return Iter\all(
                $comparedType->getTypes(),
                fn (ReflectionNamedType $comparedType): bool => $this($type, $comparedType),
            );
        }

        if ($type instanceof ReflectionIntersectionType) {
            return Iter\any(
                $type->getTypes(),
                fn (ReflectionNamedType $type): bool => $this($type, $comparedType),
            );
        }

        return $this->compareNamedTypes($type, $comparedType);
    }

    private function compareNamedTypes(ReflectionNamedType $type, ReflectionNamedType $comparedType): bool
    {
        $typeAsString         = $type->getName();
        $comparedTypeAsString = $comparedType->getName();

        if (Str\lowercase($typeAsString) === Str\lowercase($comparedTypeAsString)) {
            return true;
        }

        if ($typeAsString === 'void') {
            // everything is always contravariant to `void`
            return true;
        }

        if ($comparedTypeAsString === 'object' && self::isClassType($type)) {
            // `object` is always contravariant to any object type
            return true;
        }

        if ($comparedTypeAsString === 'iterable' && $typeAsString === 'array') {
            return true;
        }

        if ($comparedTypeAsString === 'iterable' && self::isClassType($type)) {
            // `Traversable`, and any of its implementations, can be widened to `iterable`
            $typeReflectionClass = $type->getClass();

            return $typeReflectionClass->getName() === Traversable::class
                || $typeReflectionClass->implementsInterface(Traversable::class);
        }

        if (! self::isClassType($type) || ! self::isClassType($comparedType)) {
            // All other type declarations have no variance/contravariance relationship
            return false;
        }

        $typeReflectionClass = $type->getClass();
        $comparedTypeClass   = $comparedType->getClass();

        if (Str\lowercase($typeReflectionClass->getName()) === Str\lowercase($comparedTypeClass->getName())) {
            // `self` and `parent` are resolved to the class they refer to
            return true;
        }

        if ($comparedTypeClass->isInterface()) {
            return $typeReflectionClass->implementsInterface($comparedTypeClass->getName());
        }

        return Iter\contains($typeReflectionClass->getParentClassNames(), $comparedTypeClass->getName());
    }

    /**
     * BetterReflection reports `self` and `parent` as built-in types, but they refer to a class
     */
    private static function isClassType(ReflectionNamedType $type): bool
    {
        return ! $type->isBuiltin()
            || Iter\contains(['self', 'parent'], Str\lowercase($type->getName()));
    }
}
