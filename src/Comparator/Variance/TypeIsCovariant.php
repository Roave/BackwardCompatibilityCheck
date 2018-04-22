<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Comparator\Variance;

use Roave\ApiCompare\Support\ArrayHelpers;
use Roave\BetterReflection\Reflection\ReflectionType;
use function strtolower;

/**
 * This is a simplistic covariant type check. A more appropriate approach would be to
 * have a `$type->includes($otherType)` check with actual types represented as value objects,
 * but that is a massive piece of work that should be done by importing an external library
 * instead, if this class no longer suffices.
 */
final class TypeIsCovariant
{
    public function __invoke(
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

        if ($comparedType->allowsNull() && ! $type->allowsNull()) {
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
            if ($comparedType->targetReflectionClass()->implementsInterface(\Traversable::class)) {
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

        $comparedTypeReflectionClass = $comparedType->targetReflectionClass();

        if ($type->targetReflectionClass()->isInterface()) {
            return $comparedTypeReflectionClass->implementsInterface($typeAsString);
        }

        return ArrayHelpers::stringArrayContainsString(
            $typeAsString,
            $comparedTypeReflectionClass->getParentClassNames()
        );
    }
}
