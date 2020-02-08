<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Support;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Support\ArrayHelpers;

/**
 * @covers \Roave\BackwardCompatibility\Support\ArrayHelpers
 */
final class ArrayHelpersTest extends TestCase
{
    /**
     * @param string[] $array
     *
     * @dataProvider stringArrayContainsStringValidValues
     */
    public function testInclusion(string $value, array $array, bool $expected) : void
    {
        self::assertSame($expected, ArrayHelpers::stringArrayContainsString($value, $array));
    }

    /**
     * @return array<int, array<int, string|array<int, string>|bool>>
     *
     * @psalm-return array<int, array{0: string, 1: list<string>, 2: bool}>
     */
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
}
