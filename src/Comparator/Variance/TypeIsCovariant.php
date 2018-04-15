<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\Variance;

use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\ClassReflector;
use function strtolower;
use function in_array;

/**
 * This is a simplistic covariant type check. A more appropriate approach would be to
 * have a `$type->includes($otherType)` check with actual types represented as value objects,
 * but that is a massive piece of work that should be done by importing an external library
 * instead, if this class no longer suffices.
 */
final class TypeIsCovariant
{
    public function __invoke(
        ClassReflector $reflector,
        ?ReflectionType $type,
        ?ReflectionType $comparedType
    ) : bool {
        if ($type === null) {
            // everything can be covariant to `mixed`
            return true;
        }

        if ($comparedType === null) {
            // widening a type is not covariant
            return false;
        }

        $typeAsString         = $type->__toString();
        $comparedTypeAsString = $comparedType->__toString();

        if (strtolower($typeAsString) === strtolower($comparedTypeAsString)) {
            return true;
        }

        if (strtolower($typeAsString) === 'void') {
            // nothing is covariant to `void`
            return false;
        }

        if (strtolower($typeAsString) === 'object' && ! $comparedType->isBuiltin()) {
            // `object` is not covariant to a defined class type
            return true;
        }

        if (strtolower($comparedTypeAsString) === 'array' && strtolower($typeAsString) === 'iterable') {
            // an `array` is a subset of an `iterable`, therefore covariant
            return true;
        }

        if (strtolower($typeAsString) === 'iterable' && ! $comparedType->isBuiltin()) {
            $comparedTypeReflectionClass = $reflector->reflect($comparedTypeAsString);

            if ($comparedTypeReflectionClass->implementsInterface(\Traversable::class)) {
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

        $typeReflectionClass = $reflector->reflect($typeAsString);
        $comparedTypeReflectionClass = $reflector->reflect($comparedTypeAsString);

        if ($typeReflectionClass->isInterface()) {
            return $comparedTypeReflectionClass->implementsInterface($typeAsString);
        }

        return in_array($typeAsString, $comparedTypeReflectionClass->getParentClassNames(), true);
    }
}
