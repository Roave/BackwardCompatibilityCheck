<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased\MultipleChecksOnAProperty;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased\PropertyBased;
use Roave\BetterReflection\Reflection\ReflectionProperty;

/**
 * @covers \Roave\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased\MultipleChecksOnAProperty
 */
final class MultipleChecksOnAPropertyTest extends TestCase
{
    public function testChecksAllGivenCheckers() : void
    {
        /** @var PropertyBased|MockObject $checker1 */
        $checker1 = $this->createMock(PropertyBased::class);
        /** @var PropertyBased|MockObject $checker2 */
        $checker2 = $this->createMock(PropertyBased::class);
        /** @var PropertyBased|MockObject $checker3 */
        $checker3 = $this->createMock(PropertyBased::class);

        $multiCheck = new MultipleChecksOnAProperty($checker1, $checker2, $checker3);

        /** @var ReflectionProperty|MockObject $from */
        $from = $this->createMock(ReflectionProperty::class);
        /** @var ReflectionProperty|MockObject $to */
        $to = $this->createMock(ReflectionProperty::class);

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
