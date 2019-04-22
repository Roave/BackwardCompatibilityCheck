<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use function array_fill;
use function iterator_to_array;
use function random_int;
use function serialize;
use function unserialize;

/**
 * @covers \Roave\BackwardCompatibility\Changes
 */
final class ChangesTest extends TestCase
{
    public function testMergeWith() : void
    {
        $changes1 = Changes::fromList(Change::changed('a', true));
        $changes2 = Changes::fromList(Change::removed('b', false));

        $frozen1 = unserialize(serialize($changes1));
        $frozen2 = unserialize(serialize($changes2));

        Assertion::assertChangesEqual(
            Changes::fromList(
                Change::changed('a', true),
                Change::removed('b', false)
            ),
            $changes1->mergeWith($changes2)
        );
        Assertion::assertChangesEqual(
            Changes::fromList(
                Change::removed('b', false),
                Change::changed('a', true)
            ),
            $changes2->mergeWith($changes1)
        );

        self::assertEquals($frozen1, $changes1, 'Original Changes instance not mutated');
        self::assertEquals($frozen2, $changes2, 'Original Changes instance not mutated');
    }

    public function testFromIteratorBuffersAllChangesWithoutLoadingThemEagerly() : void
    {
        $producedValues  = 0;
        $changesProvider = static function () use (& $producedValues) {
            $producedValues += 1;

            yield Change::changed('a', true);

            $producedValues += 1;

            yield Change::changed('b', false);
        };

        $changes = Changes::fromIterator($changesProvider());

        self::assertSame(0, $producedValues);

        // Nesting one level deep - should still not traverse the iterator eagerly
        $changes = Changes::fromIterator($changes);

        self::assertSame(0, $producedValues);

        $expectedChanges = [
            Change::changed('a', true),
            Change::changed('b', false),
        ];

        self::assertEquals($expectedChanges, iterator_to_array($changes));
        self::assertEquals(
            $expectedChanges,
            iterator_to_array($changes),
            'Changes can be iterated upon more than once (they are buffered)'
        );
        self::assertCount(2, $changes);
    }

    public function testMergeWithPreservesOriginalInstanceIfMergedWithEmptyChanges() : void
    {
        $changes = Changes::fromList(Change::changed('a', true));

        Assertion::assertChangesEqual($changes, $changes->mergeWith(Changes::empty()));
    }

    public function testFromList() : void
    {
        $change = Change::added('added', true);

        self::assertSame(
            [$change],
            iterator_to_array(Changes::fromList($change))
        );
    }

    public function testCount() : void
    {
        $count = random_int(1, 10);

        self::assertCount(
            $count,
            Changes::fromList(...array_fill(0, $count, Change::added('foo', true)))
        );
    }
}
