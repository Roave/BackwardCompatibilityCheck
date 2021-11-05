<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility;

use function assert;

/**
 * This utility is mostly for test purposes only: it takes values and makes sure they match
 * an expected type, allowing us to ignore some of the type errors that psalm/phpstan would otherwise
 * pick up. It still asserts on the given values.
 */
final class TypeRestriction
{
    /**
     * @psalm-param    ObjectishParameterType|null $value
     *
     * @psalm-return   ObjectishParameterType
     *
     * @psalm-template ObjectishParameterType of object
     */
    public static function object(?object $value): object
    {
        assert($value !== null);

        return $value;
    }
}
