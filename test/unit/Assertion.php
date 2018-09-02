<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility;

use PHPUnit\Framework\Assert;
use Roave\BackwardCompatibility\Changes;

abstract class Assertion
{
    final private function __construct()
    {
    }

    public static function assertChangesEqual(
        Changes $expected,
        Changes $actual,
        string $message = ''
    ) : void {
        // Forces eager initialisation of the `Changes` instances, allowing us to compare them by value
        Assert::assertSame(count($expected), count($actual));
        Assert::assertEquals($expected, $actual, $message);
    }
}
