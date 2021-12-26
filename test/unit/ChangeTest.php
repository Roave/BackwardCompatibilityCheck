<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility;

use Exception;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;

use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\Change
 */
final class ChangeTest extends TestCase
{
    public function testAdded(): void
    {
        $changeText = uniqid('changeText', true);
        $change     = Change::added($changeText, false);
        self::assertSame('     ADDED: ' . $changeText, (string) $change);
        self::assertTrue($change->isAdded());
        self::assertFalse($change->isChanged());
        self::assertFalse($change->isRemoved());
        self::assertFalse($change->isSkipped());
    }

    public function testBcAdded(): void
    {
        $changeText = uniqid('changeText', true);
        $change     = Change::added($changeText, true);
        self::assertSame('[BC] ADDED: ' . $changeText, (string) $change);
        self::assertTrue($change->isAdded());
        self::assertFalse($change->isChanged());
        self::assertFalse($change->isRemoved());
        self::assertFalse($change->isSkipped());
    }

    public function testChanged(): void
    {
        $changeText = uniqid('changeText', true);
        $change     = Change::changed($changeText, false);
        self::assertSame('     CHANGED: ' . $changeText, (string) $change);
        self::assertFalse($change->isAdded());
        self::assertTrue($change->isChanged());
        self::assertFalse($change->isRemoved());
    }

    public function testCanRecordFileLineAndColumnPosition(): void
    {
        $change = Change::changed('foo', false)
            ->onFile('a-file')
            ->onLine(123)
            ->onColumn(456);

        self::assertSame('a-file', $change->file);
        self::assertSame(123, $change->line);
        self::assertSame(456, $change->column);
    }

    public function testBcChanged(): void
    {
        $changeText = uniqid('changeText', true);
        $change     = Change::changed($changeText, true);
        self::assertSame('[BC] CHANGED: ' . $changeText, (string) $change);
        self::assertFalse($change->isAdded());
        self::assertTrue($change->isChanged());
        self::assertFalse($change->isRemoved());
        self::assertFalse($change->isSkipped());
    }

    public function testRemoved(): void
    {
        $changeText = uniqid('changeText', true);
        $change     = Change::removed($changeText, false);
        self::assertSame('     REMOVED: ' . $changeText, (string) $change);
        self::assertFalse($change->isAdded());
        self::assertFalse($change->isChanged());
        self::assertTrue($change->isRemoved());
        self::assertFalse($change->isSkipped());
    }

    public function testBcRemoved(): void
    {
        $changeText = uniqid('changeText', true);
        $change     = Change::removed($changeText, true);
        self::assertSame('[BC] REMOVED: ' . $changeText, (string) $change);
        self::assertFalse($change->isAdded());
        self::assertFalse($change->isChanged());
        self::assertTrue($change->isRemoved());
        self::assertFalse($change->isSkipped());
    }

    public function testSkippedDueToFailure(): void
    {
        $failure = new Exception('changeText');
        $change  = Change::skippedDueToFailure($failure);
        self::assertSame('[BC] SKIPPED: ' . $failure->getMessage(), (string) $change);
        self::assertFalse($change->isAdded());
        self::assertFalse($change->isChanged());
        self::assertFalse($change->isRemoved());
        self::assertTrue($change->isSkipped());
    }
}
