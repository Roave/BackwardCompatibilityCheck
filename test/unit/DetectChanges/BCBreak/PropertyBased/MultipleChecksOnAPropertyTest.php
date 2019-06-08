<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\MultipleChecksOnAProperty;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyBased;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use RoaveTest\BackwardCompatibility\Assertion;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\MultipleChecksOnAProperty
 */
final class MultipleChecksOnAPropertyTest extends TestCase
{
    public function testChecksAllGivenCheckers() : void
    {
        /** @var PropertyBased&MockObject $checker1 */
        $checker1 = $this->createMock(PropertyBased::class);
        /** @var PropertyBased&MockObject $checker2 */
        $checker2 = $this->createMock(PropertyBased::class);
        /** @var PropertyBased&MockObject $checker3 */
        $checker3 = $this->createMock(PropertyBased::class);

        $multiCheck = new MultipleChecksOnAProperty($checker1, $checker2, $checker3);

        /** @var ReflectionProperty&MockObject $from */
        $from = $this->createMock(ReflectionProperty::class);
        /** @var ReflectionProperty&MockObject $to */
        $to = $this->createMock(ReflectionProperty::class);

        $checker1
            ->expects(self::once())
            ->method('__invoke')
            ->with($from, $to)
            ->willReturn(Changes::fromList(Change::added('1', true)));

        $checker2
            ->expects(self::once())
            ->method('__invoke')
            ->with($from, $to)
            ->willReturn(Changes::fromList(Change::added('2', true)));

        $checker3
            ->expects(self::once())
            ->method('__invoke')
            ->with($from, $to)
            ->willReturn(Changes::fromList(Change::added('3', true)));

        Assertion::assertChangesEqual(
            Changes::fromList(
                Change::added('1', true),
                Change::added('2', true),
                Change::added('3', true)
            ),
            $multiCheck->__invoke($from, $to)
        );
    }
}
