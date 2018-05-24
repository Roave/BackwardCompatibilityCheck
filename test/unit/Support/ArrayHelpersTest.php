<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Support;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Support\ArrayHelpers;
use stdClass;

/**
 * @covers \Roave\BackwardCompatibility\Support\ArrayHelpers
 */
final class ArrayHelpersTest extends TestCase
{
    /**
     * @dataProvider stringArrayContainsStringValidValues
     *
     * @param string[] $array
     */
    public function testInclusion(string $value, array $array, bool $expected) : void
    {
        self::assertSame($expected, ArrayHelpers::stringArrayContainsString($value, $array));
    }

    /** @return (string|string[]|bool)[][] */
    public function stringArrayContainsStringValidValues() : array
    {
        return [
            [
                '',
                [],
                false,
            ],
            [
                '',
                [''],
                true,
            ],
            [
                '0',
                [''],
                false,
            ],
            [
                '',
                ['0'],
                false,
            ],
            [
                'foo',
                ['foo', 'bar', 'baz'],
                true,
            ],
            [
                'foo',
                ['bar', 'baz'],
                false,
            ],
            [
                'foo',
                ['foo', 'foo', 'foo'],
                true,
            ],
        ];
    }

    /**
     * @dataProvider invalidStringArrays
     *
     * @param mixed[] $array
     */
    public function testRejectsArraysWithNonStringValues(array $array) : void
    {
        $this->expectException(\InvalidArgumentException::class);

        ArrayHelpers::stringArrayContainsString('', $array);
    }

    /** @return mixed[][] */
    public function invalidStringArrays() : array
    {
        return [
            [[null]],
            [[true]],
            [[123]],
            [[123.45]],
            [[[]]],
            [[new stdClass()]],
        ];
    }
}
