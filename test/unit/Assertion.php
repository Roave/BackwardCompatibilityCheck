<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility;

use Generator;
use PHPUnit\Framework\Assert;
use ReflectionProperty;
use Roave\BackwardCompatibility\Changes;
use function count;

abstract class Assertion
{
    /** @var ReflectionProperty|null */
    private static $reflectionGenerator;

    final private function __construct()
    {
    }

    public static function assertChangesEqual(
        Changes $expected,
        Changes $actual,
        string $message = ''
    ) : void {
        Assert::assertInstanceOf(
            Generator::class,
            self::reflectionGenerator()->getValue($actual),
            'Generator must NOT be exhausted'
        );
        // Forces eager initialisation of the `Changes` instances, allowing us to compare them by value
        Assert::assertSame(count($expected), count($actual));
        Assert::assertEquals($expected, $actual, $message);
    }

    private static function reflectionGenerator() : ReflectionProperty
    {
        if (self::$reflectionGenerator) {
            return self::$reflectionGenerator;
        }

        self::$reflectionGenerator = new ReflectionProperty(Changes::class, 'generator');

        self::$reflectionGenerator->setAccessible(true);

        return self::$reflectionGenerator;
    }
}
