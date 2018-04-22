<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased\FunctionBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased\MultipleChecksOnAFunction;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;

/**
 * @covers \Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased\MultipleChecksOnAFunction
 */
final class MultipleChecksOnAFunctionTest extends TestCase
{
    public function testChecksAllGivenCheckers() : void
    {
        /** @var FunctionBased|MockObject $checker1 */
        $checker1 = $this->createMock(FunctionBased::class);
        /** @var FunctionBased|MockObject $checker2 */
        $checker2 = $this->createMock(FunctionBased::class);
        /** @var FunctionBased|MockObject $checker3 */
        $checker3 = $this->createMock(FunctionBased::class);

        $multiCheck = new MultipleChecksOnAFunction($checker1, $checker2, $checker3);

        /** @var ReflectionFunctionAbstract|MockObject $from */
        $from = $this->createMock(ReflectionFunctionAbstract::class);
        /** @var ReflectionFunctionAbstract|MockObject $to */
        $to = $this->createMock(ReflectionFunctionAbstract::class);

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
