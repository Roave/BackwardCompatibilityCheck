<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility;

use function assert;
use function is_array;

/**
 * This utility is mostly for test purposes only: it takes values and makes sure they match
 * an expected type, allowing us to ignore some of the type errors that psalm/phpstan would otherwise
 * pick up. It still asserts on the given values.
 */
final class TypeRestriction
{
    /**
     * @param mixed[]|mixed $value
     *
     * @return mixed[]
     *
     * @psalm-template ArrayishParameterType of array
     * @psalm-param    ArrayishParameterType|mixed $value
     * @psalm-return   ArrayishParameterType
     */
    public static function array($value): array
    {
        assert(is_array($value));

        return $value;
    }

    /**
     * @psalm-template ObjectishParameterType of object
     * @psalm-param    ObjectishParameterType|null $value
     * @psalm-return   ObjectishParameterType
     */
    public static function object(?object $value): object
    {
        assert($value !== null);

        return $value;
    }
}
