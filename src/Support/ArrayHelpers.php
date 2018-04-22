<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Support;

use Assert\Assert;
use function array_flip;
use function array_key_exists;

final class ArrayHelpers
{
    /**
     * Yes, this is just a very pedantic version of `in_array()`, written to avoid mutations and
     * designed to throw an exception if `$arrayOfStrings` is not a `string[]` as requested.
     *
     * @param string[] $arrayOfStrings
     *
     * @throws \InvalidArgumentException
     */
    public static function stringArrayContainsString(string $value, array $arrayOfStrings) : bool
    {
        Assert::that($arrayOfStrings)->all()->string();

        return array_key_exists($value, array_flip($arrayOfStrings));
    }
}
