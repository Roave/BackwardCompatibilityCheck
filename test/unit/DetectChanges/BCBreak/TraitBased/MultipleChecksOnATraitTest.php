<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\DetectChanges\BCBreak\TraitBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\DetectChanges\BCBreak\TraitBased\MultipleChecksOnATrait;
use Roave\ApiCompare\DetectChanges\BCBreak\TraitBased\TraitBased;
use Roave\BetterReflection\Reflection\ReflectionClass;

/**
 * @covers \Roave\ApiCompare\DetectChanges\BCBreak\TraitBased\MultipleChecksOnATrait
 */
final class MultipleChecksOnATraitTest extends TestCase
{
    public function testChecksAllGivenCheckers() : void
    {
        /** @var TraitBased|MockObject $checker1 */
        $checker1 = $this->createMock(TraitBased::class);
        /** @var TraitBased|MockObject $checker2 */
        $checker2 = $this->createMock(TraitBased::class);
        /** @var TraitBased|MockObject $checker3 */
        $checker3 = $this->createMock(TraitBased::class);

        $multiCheck = new MultipleChecksOnATrait($checker1, $checker2, $checker3);

        /** @var ReflectionClass|MockObject $from */
        $from = $this->createMock(ReflectionClass::class);
        /** @var ReflectionClass|MockObject $to */
        $to = $this->createMock(ReflectionClass::class);

        $checker1
            ->expects(self::once())
            ->method('compare')
            ->with($from, $to)
            ->willReturn(Changes::fromArray([
                Change::added('1', true),
            ]));

        $checker2
            ->expects(self::once())
            ->method('compare')
            ->with($from, $to)
            ->willReturn(Changes::fromArray([
                Change::added('2', true),
            ]));

        $checker3
            ->expects(self::once())
            ->method('compare')
            ->with($from, $to)
            ->willReturn(Changes::fromArray([
                Change::added('3', true),
            ]));

        $this->assertEquals(
            Changes::fromArray([
                Change::added('1', true),
                Change::added('2', true),
                Change::added('3', true),
            ]),
            $multiCheck->compare($from, $to)
        );
    }
}
