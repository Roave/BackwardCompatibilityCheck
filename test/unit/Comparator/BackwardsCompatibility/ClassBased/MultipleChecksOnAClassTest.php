<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\ClassBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\MultipleChecksOnAClass;
use Roave\BetterReflection\Reflection\ReflectionClass;

/**
 * @covers \Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\MultipleChecksOnAClass
 */
final class MultipleChecksOnAClassTest extends TestCase
{
    public function testChecksAllGivenCheckers() : void
    {
        /** @var ClassBased|MockObject $checker1 */
        $checker1 = $this->createMock(ClassBased::class);
        /** @var ClassBased|MockObject $checker2 */
        $checker2 = $this->createMock(ClassBased::class);
        /** @var ClassBased|MockObject $checker3 */
        $checker3 = $this->createMock(ClassBased::class);

        $multiCheck = new MultipleChecksOnAClass($checker1, $checker2, $checker3);

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
