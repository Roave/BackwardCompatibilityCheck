<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\Variance;

use Psl\Iter;
use Psl\Str;
use Roave\BetterReflection\Reflection\ReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionType;
use Traversable;

/**
 * This is a simplistic covariant type check. A more appropriate approach would be to
 * have a `$type->includes($otherType)` check with actual types represented as value objects,
 * but that is a massive piece of work that should be done by importing an external library
 * instead, if this class no longer suffices.
 *
 * @TODO introduce union/intersection type support here
 */
final class TypeIsCovariant
{
    public function __invoke(
        ?ReflectionType $type,
        ?ReflectionType $comparedType
    ): bool {
        if ($type === null) {
            // everything can be covariant to `mixed`
            return true;
        }

        if ($comparedType === null) {
            // widening a type is not covariant
            return false;
        }

        if (! $type instanceof ReflectionNamedType || ! $comparedType instanceof ReflectionNamedType) {
            // @TODO we'll assume everyting is fine, for now - union and intersection types still need test additions
            return true;
        }

        if ($comparedType->allowsNull() && ! $type->allowsNull()) {
            return false;
        }

        $typeAsString         = $type->getName();
        $comparedTypeAsString = $comparedType->getName();

        if (Str\lowercase($typeAsString) === Str\lowercase($comparedTypeAsString)) {
            return true;
        }

        if ($typeAsString === 'void') {
            // nothing is covariant to `void`
            return false;
        }

        if ($typeAsString === 'object' && ! $comparedType->isBuiltin()) {
            // `object` is not covariant to a defined class type
            return true;
        }

        if ($comparedTypeAsString === 'array' && $typeAsString === 'iterable') {
            // an `array` is a subset of an `iterable`, therefore covariant
            return true;
        }

        if ($typeAsString === 'iterable' && ! $comparedType->isBuiltin()) {
            $comparedTypeReflectionClass = $comparedType->getClass();

            if ($comparedTypeReflectionClass->implementsInterface(Traversable::class)) {
                // `iterable` can be restricted via any `Iterator` implementation
                return true;
            }
        }

        if ($type->isBuiltin() !== $comparedType->isBuiltin()) {
            // other known built-in types are never covariant with non-built-in types
            return false;
        }

        if ($type->isBuiltin()) {
            // all other built-in type declarations have no variance/contravariance relationship
            return false;
        }

        $originalTypeReflectionClass = $type->getClass();
        $comparedTypeReflectionClass = $comparedType->getClass();

        if ($originalTypeReflectionClass->isInterface()) {
            return $comparedTypeReflectionClass->implementsInterface($originalTypeReflectionClass->getName());
        }

        return Iter\contains(
            $comparedTypeReflectionClass->getParentClassNames(),
            $originalTypeReflectionClass->getName()
        );
    }
}
