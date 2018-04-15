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
        self::assertSame(
            '     ADDED: ' . $changeText,
            (string) Change::added($changeText, false)
        );
    }

    public function testBcAdded() : void
    {
        $changeText = uniqid('changeText', true);
        self::assertSame(
            '[BC] ADDED: ' . $changeText,
            (string) Change::added($changeText, true)
        );
    }

    public function testChanged() : void
    {
        $changeText = uniqid('changeText', true);
        self::assertSame(
            '     CHANGED: ' . $changeText,
            (string) Change::changed($changeText, false)
        );
    }

    public function testBcChanged() : void
    {
        $changeText = uniqid('changeText', true);
        self::assertSame(
            '[BC] CHANGED: ' . $changeText,
            (string) Change::changed($changeText, true)
        );
    }

    public function testRemoved() : void
    {
        $changeText = uniqid('changeText', true);
        self::assertSame(
            '     REMOVED: ' . $changeText,
            (string) Change::removed($changeText, false)
        );
    }

    public function testBcRemoved() : void
    {
        $changeText = uniqid('changeText', true);
        self::assertSame(
            '[BC] REMOVED: ' . $changeText,
            (string) Change::removed($changeText, true)
        );
    }
}
