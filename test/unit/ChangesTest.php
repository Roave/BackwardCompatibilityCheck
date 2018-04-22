<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use function iterator_to_array;
use function random_bytes;
use function random_int;
use function serialize;
use function uniqid;
use function unserialize;

/**
 * @covers \Roave\ApiCompare\Changes
 */
final class ChangesTest extends TestCase
{
    public function testMergeWith() : void
    {
        $changes1 = Changes::fromArray([
            Change::changed('a', true),
        ]);

        $changes2 = Changes::fromArray([
            Change::removed('b', false),
        ]);

        $frozen1 = unserialize(serialize($changes1));
        $frozen2 = unserialize(serialize($changes2));

        self::assertEquals(
            Changes::fromArray([
                Change::changed('a', true),
                Change::removed('b', false),
            ]),
            $changes1->mergeWith($changes2)
        );
        self::assertEquals(
            Changes::fromArray([
                Change::removed('b', false),
                Change::changed('a', true),
            ]),
            $changes2->mergeWith($changes1)
        );

        self::assertEquals($frozen1, $changes1, 'Original Changes instance not mutated');
        self::assertEquals($frozen2, $changes2, 'Original Changes instance not mutated');
    }

    public function testFromArray() : void
    {
        $change = Change::added('added', true);

        self::assertSame(
            [$change],
            iterator_to_array(Changes::fromArray([$change]))
        );
    }

    /**
     * @return mixed[]
     * @throws \Exception
     */
    public function invalidChangeProvider() : array
    {
        return [
            [random_int(1, 100)],
            [random_int(1, 100) / 1000],
            [uniqid('string', true)],
            [random_bytes(24)],
            [[]],
            [null],
            [true],
            [false],
            [new \stdClass()],
        ];
    }

    /**
     * @param mixed $invalidChange
     * @dataProvider invalidChangeProvider
     */
    public function testFromArrayThowsExceptionWhenInvalidChangePassed($invalidChange) : void
    {
        $this->expectException(InvalidArgumentException::class);
        Changes::fromArray([$invalidChange]);
    }

    public function testWithAddedChange() : void
    {
        $change = Change::added('added', true);

        self::assertSame(
            [$change],
            iterator_to_array(Changes::new()->withAddedChange($change))
        );
    }

    public function testCount() : void
    {
        $count = random_int(1, 10);

        self::assertCount(
            $count,
            Changes::fromArray(array_fill(0, $count, Change::added('foo', true)))
        );
    }
}
