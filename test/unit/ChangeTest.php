<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use function uniqid;

/**
 * @covers \Roave\ApiCompare\Change
 */
final class ChangeTest extends TestCase
{
    public function testAdded() : void
    {
        $changeText = uniqid('changeText', true);
        $change = Change::added($changeText, false);
        self::assertSame('     ADDED: ' . $changeText, (string) $change);
        self::assertTrue($change->isAdded());
        self::assertFalse($change->isChanged());
        self::assertFalse($change->isRemoved());
    }

    public function testBcAdded() : void
    {
        $changeText = uniqid('changeText', true);
        $change = Change::added($changeText, true);
        self::assertSame('[BC] ADDED: ' . $changeText, (string) $change);
        self::assertTrue($change->isAdded());
        self::assertFalse($change->isChanged());
        self::assertFalse($change->isRemoved());
    }

    public function testChanged() : void
    {
        $changeText = uniqid('changeText', true);
        $change = Change::changed($changeText, false);
        self::assertSame('     CHANGED: ' . $changeText, (string) $change);
        self::assertFalse($change->isAdded());
        self::assertTrue($change->isChanged());
        self::assertFalse($change->isRemoved());
    }

    public function testBcChanged() : void
    {
        $changeText = uniqid('changeText', true);
        $change = Change::changed($changeText, true);
        self::assertSame('[BC] CHANGED: ' . $changeText, (string) $change);
        self::assertFalse($change->isAdded());
        self::assertTrue($change->isChanged());
        self::assertFalse($change->isRemoved());
    }

    public function testRemoved() : void
    {
        $changeText = uniqid('changeText', true);
        $change = Change::removed($changeText, false);
        self::assertSame('     REMOVED: ' . $changeText, (string) $change);
        self::assertFalse($change->isAdded());
        self::assertFalse($change->isChanged());
        self::assertTrue($change->isRemoved());
    }

    public function testBcRemoved() : void
    {
        $changeText = uniqid('changeText', true);
        $change = Change::removed($changeText, true);
        self::assertSame('[BC] REMOVED: ' . $changeText, (string) $change);
        self::assertFalse($change->isAdded());
        self::assertFalse($change->isChanged());
        self::assertTrue($change->isRemoved());
    }
}
