<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\Variance;

use Psl\Iter;
use Psl\Str;
use Roave\BetterReflection\Reflection\ReflectionType;

/**
 * This is a simplistic contravariant type check. A more appropriate approach would be to
 * have a `$type->includes($otherType)` check with actual types represented as value objects,
 * but that is a massive piece of work that should be done by importing an external library
 * instead, if this class no longer suffices.
 */
final class TypeIsContravariant
{
    public function __invoke(
        ?ReflectionType $type,
        ?ReflectionType $comparedType
    ): bool {
        if ($comparedType === null) {
            return true;
        }

        if ($type === null) {
            // nothing can be contravariant to `mixed` besides `mixed` itself (handled above)
            return false;
        }

        if ($type->allowsNull() && ! $comparedType->allowsNull()) {
            return false;
        }

        $typeAsString         = $type->__toString();
        $comparedTypeAsString = $comparedType->__toString();

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

        $typeReflectionClass = $type->targetReflectionClass();

        if ($comparedType->targetReflectionClass()->isInterface()) {
            return $typeReflectionClass->implementsInterface($comparedTypeAsString);
        }

        return Iter\contains($typeReflectionClass->getParentClassNames(), $comparedTypeAsString);
    }
}
