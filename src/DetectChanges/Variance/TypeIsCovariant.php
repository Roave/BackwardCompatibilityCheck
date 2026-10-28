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
 * This is a simplistic covariant type check. A more appropriate approach would be to
 * have a `$type->includes($otherType)` check with actual types represented as value objects,
 * but that is a massive piece of work that should be done by importing an external library
 * instead, if this class no longer suffices.
 */
final class TypeIsCovariant
{
    public function __invoke(
        ReflectionIntersectionType|ReflectionUnionType|ReflectionNamedType|null $type,
        ReflectionIntersectionType|ReflectionUnionType|ReflectionNamedType|null $comparedType,
    ): bool {
        if ($type === null) {
            // everything can be covariant to `mixed`
            return true;
        }

        if ($comparedType === null) {
            // widening a type is not covariant
            return false;
        }

        if ($comparedType instanceof ReflectionUnionType) {
            return Iter\all(
                $comparedType->getTypes(),
                fn (ReflectionNamedType|ReflectionIntersectionType $comparedType): bool => $this($type, $comparedType),
            );
        }

        if ($type instanceof ReflectionUnionType) {
            return Iter\any(
                $type->getTypes(),
                fn (ReflectionNamedType|ReflectionIntersectionType $type): bool => $this($type, $comparedType),
            );
        }

        if ($type instanceof ReflectionIntersectionType) {
            return Iter\all(
                $type->getTypes(),
                fn (ReflectionNamedType $type): bool => $this($type, $comparedType),
            );
        }

        if ($comparedType instanceof ReflectionIntersectionType) {
            return Iter\any(
                $comparedType->getTypes(),
                fn (ReflectionNamedType $comparedType): bool => $this($type, $comparedType),
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

        if ($typeAsString === 'mixed' || $comparedTypeAsString === 'never') {
            // everything is covariant to `mixed` or `never`
            return true;
        }

        if ($typeAsString === 'object' && self::isClassType($comparedType)) {
            // `object` is not covariant to a defined class type
            return true;
        }

        if ($comparedTypeAsString === 'array' && $typeAsString === 'iterable') {
            // an `array` is a subset of an `iterable`, therefore covariant
            return true;
        }

        if ($typeAsString === 'iterable' && self::isClassType($comparedType)) {
            $comparedTypeReflectionClass = $comparedType->getClass();

            if (
                $comparedTypeReflectionClass->getName() === Traversable::class
                || $comparedTypeReflectionClass->implementsInterface(Traversable::class)
            ) {
                // `iterable` can be restricted via `Traversable`, or any of its implementations
                return true;
            }
        }

        if (! self::isClassType($type) || ! self::isClassType($comparedType)) {
            // all other built-in type declarations have no variance/contravariance relationship
            return false;
        }

        if (Str\lowercase($typeAsString) === 'static') {
            // `static` may refer to any child class, so it is only covariant to itself
            return false;
        }

        $originalTypeReflectionClass = $type->getClass();
        $comparedTypeReflectionClass = $comparedType->getClass();

        if (
            Str\lowercase($originalTypeReflectionClass->getName())
            === Str\lowercase($comparedTypeReflectionClass->getName())
        ) {
            // `self`, `parent` and `static` are resolved to the class they refer to
            return true;
        }

        if ($originalTypeReflectionClass->isInterface()) {
            return $comparedTypeReflectionClass->implementsInterface($originalTypeReflectionClass->getName());
        }

        return Iter\contains(
            $comparedTypeReflectionClass->getParentClassNames(),
            $originalTypeReflectionClass->getName(),
        );
    }

    /**
     * BetterReflection reports `self`, `parent` and `static` as built-in types, but they refer to a class
     */
    private static function isClassType(ReflectionNamedType $type): bool
    {
        return ! $type->isBuiltin()
            || Iter\contains(['self', 'parent', 'static'], Str\lowercase($type->getName()));
    }
}
