<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\Variance;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\ClassReflector;
use function in_array;
use function strtolower;

/**
 * This is a simplistic contravariant type check. A more appropriate approach would be to
 * have a `$type->includes($otherType)` check with actual types represented as value objects,
 * but that is a massive piece of work that should be done by importing an external library
 * instead, if this class no longer suffices.
 */
final class TypeIsContravariant
{
    public function __invoke(
        ClassReflector $reflector,
        ?ReflectionType $type,
        ?ReflectionType $comparedType
    ) : bool {
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

        if (strtolower($typeAsString) === strtolower($comparedTypeAsString)) {
            return true;
        }

        if (strtolower($typeAsString) === 'void') {
            // everything is always contravariant to `void`
            return true;
        }

        if (strtolower($comparedTypeAsString) === 'object' && ! $type->isBuiltin()) {
            // `object` is always contravariant to any object type
            return true;
        }

        if (strtolower($comparedTypeAsString) === 'iterable' && strtolower($typeAsString) === 'array') {
            return true;
        }

        if ($type->isBuiltin() !== $comparedType->isBuiltin()) {
            return false;
        }

        if ($type->isBuiltin()) {
            // All other type declarations have no variance/contravariance relationship
            return false;
        }

        /** @var ReflectionClass $typeReflectionClass */
        $typeReflectionClass = $reflector->reflect($typeAsString);
        /** @var ReflectionClass $comparedTypeReflectionClass */
        $comparedTypeReflectionClass = $reflector->reflect($comparedTypeAsString);

        if ($comparedTypeReflectionClass->isInterface()) {
            return $typeReflectionClass->implementsInterface($comparedTypeAsString);
        }

        return in_array($comparedTypeAsString, $typeReflectionClass->getParentClassNames(), true);
    }
}
