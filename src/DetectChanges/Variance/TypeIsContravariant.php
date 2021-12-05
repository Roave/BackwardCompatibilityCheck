<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\Variance;

use Psl\Iter;
use Psl\Str;
use Roave\BetterReflection\Reflection\ReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflection\ReflectionUnionType;

/**
 * This is a simplistic contravariant type check. A more appropriate approach would be to
 * have a `$type->includes($otherType)` check with actual types represented as value objects,
 * but that is a massive piece of work that should be done by importing an external library
 * instead, if this class no longer suffices.
 *
 * @TODO introduce union/intersection type support here
 */
final class TypeIsContravariant
{
    public function __invoke(
        ?ReflectionType $type,
        ?ReflectionType $comparedType
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
                fn (ReflectionNamedType $type): bool => $this($type, $comparedType)
            );
        }

        if ($comparedType instanceof ReflectionUnionType) {
            return Iter\any(
                $comparedType->getTypes(),
                fn (ReflectionNamedType $comparedType): bool => $this($type, $comparedType)
            );
        }

        if (! $type instanceof ReflectionNamedType || ! $comparedType instanceof ReflectionNamedType) {
            // @TODO we'll assume everyting is fine, for now - union and intersection types still need test additions
            return true;
        }

        return $this->compareNamedTypes($type, $comparedType);
    }

    private function compareNamedTypes(ReflectionNamedType $type, ReflectionNamedType $comparedType): bool
    {
        if ($type->allowsNull() && ! $comparedType->allowsNull()) {
            return false;
        }

        $typeAsString         = $type->getName();
        $comparedTypeAsString = $comparedType->getName();

        if (Str\lowercase($typeAsString) === Str\lowercase($comparedTypeAsString)) {
            return true;
        }

        if ($typeAsString === 'void') {
            // everything is always contravariant to `void`
            return true;
        }

        if ($comparedTypeAsString === 'object' && ! $type->isBuiltin()) {
            // `object` is always contravariant to any object type
            return true;
        }

        if ($comparedTypeAsString === 'iterable' && $typeAsString === 'array') {
            return true;
        }

        if ($type->isBuiltin() !== $comparedType->isBuiltin()) {
            return false;
        }

        if ($type->isBuiltin()) {
            // All other type declarations have no variance/contravariance relationship
            return false;
        }

        $typeReflectionClass = $type->getClass();
        $comparedTypeClass   = $comparedType->getClass();

        if ($comparedTypeClass->isInterface()) {
            return $typeReflectionClass->implementsInterface($comparedTypeClass->getName());
        }

        return Iter\contains($typeReflectionClass->getParentClassNames(), $comparedTypeClass->getName());
    }
}
