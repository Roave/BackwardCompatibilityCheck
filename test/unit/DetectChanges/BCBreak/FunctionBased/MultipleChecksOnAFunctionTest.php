<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\FunctionBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\MultipleChecksOnAFunction;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use RoaveTest\BackwardCompatibility\Assertion;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\MultipleChecksOnAFunction
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
