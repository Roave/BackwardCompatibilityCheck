<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Support;

use Assert\Assert;
use InvalidArgumentException;
use function in_array;

/**
 * @internal this is a support class of this library, and should NOT be used outside of it
 */
final class ArrayHelpers
{
    /**
     * Yes, this is just a very pedantic version of `in_array()`, written to avoid mutations and
     * designed to throw an exception if `$arrayOfStrings` is not a `string[]` as requested.
     *
     * @param string[] $arrayOfStrings
     *
     * @throws InvalidArgumentException
     */
    public static function stringArrayContainsString(string $value, array $arrayOfStrings) : bool
    {
        Assert::that($arrayOfStrings)->all()->string();

        return in_array($value, $arrayOfStrings);
    }
}
