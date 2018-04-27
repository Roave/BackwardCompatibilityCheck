<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased\ClassConstantBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased\MultipleChecksOnAClassConstant;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased\MultipleChecksOnAClassConstant
 */
final class MultipleChecksOnAClassConstantTest extends TestCase
{
    public function testChecksAllGivenCheckers() : void
    {
        /** @var ConstantBased|MockObject $checker1 */
        $checker1 = $this->createMock(ClassConstantBased::class);
        /** @var ConstantBased|MockObject $checker2 */
        $checker2 = $this->createMock(ClassConstantBased::class);
        /** @var ConstantBased|MockObject $checker3 */
        $checker3 = $this->createMock(ClassConstantBased::class);

        $multiCheck = new MultipleChecksOnAClassConstant($checker1, $checker2, $checker3);

        /** @var ReflectionClassConstant|MockObject $from */
        $from = $this->createMock(ReflectionClassConstant::class);
        /** @var ReflectionClassConstant|MockObject $to */
        $to = $this->createMock(ReflectionClassConstant::class);

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

        $this->assertEquals(
            Changes::fromList(
                Change::added('1', true),
                Change::added('2', true),
                Change::added('3', true)
            ),
            $multiCheck->__invoke($from, $to)
        );
    }
}
