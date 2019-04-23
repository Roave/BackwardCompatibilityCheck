<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MultipleChecksOnAMethod;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use RoaveTest\BackwardCompatibility\Assertion;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MultipleChecksOnAMethod
 */
final class MultipleChecksOnAMethodTest extends TestCase
{
    public function testChecksAllGivenCheckers() : void
    {
        /** @var MethodBased&MockObject $checker1 */
        $checker1 = $this->createMock(MethodBased::class);
        /** @var MethodBased&MockObject $checker2 */
        $checker2 = $this->createMock(MethodBased::class);
        /** @var MethodBased&MockObject $checker3 */
        $checker3 = $this->createMock(MethodBased::class);

        $multiCheck = new MultipleChecksOnAMethod($checker1, $checker2, $checker3);

        /** @var ReflectionMethod&MockObject $from */
        $from = $this->createMock(ReflectionMethod::class);
        /** @var ReflectionMethod&MockObject $to */
        $to = $this->createMock(ReflectionMethod::class);

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
