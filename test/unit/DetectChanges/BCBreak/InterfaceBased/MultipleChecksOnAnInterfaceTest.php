<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\InterfaceBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\MultipleChecksOnAnInterface;
use Roave\BetterReflection\Reflection\ReflectionClass;
use RoaveTest\BackwardCompatibility\Assertion;

/** @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\MultipleChecksOnAnInterface */
final class MultipleChecksOnAnInterfaceTest extends TestCase
{
    public function testChecksAllGivenCheckers(): void
    {
        $checker1 = $this->createMock(InterfaceBased::class);
        $checker2 = $this->createMock(InterfaceBased::class);
        $checker3 = $this->createMock(InterfaceBased::class);

        $multiCheck = new MultipleChecksOnAnInterface($checker1, $checker2, $checker3);

        $from = $this->createMock(ReflectionClass::class);
        $to   = $this->createMock(ReflectionClass::class);

        $to->method('getFileName')
            ->willReturn('foo.php');
        $to->method('getStartLine')
            ->willReturn(10);
        $to->method('getStartColumn')
            ->willReturn(5);

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
                Change::added('1', true)
                    ->onFile('foo.php')
                    ->onLine(10)
                    ->onColumn(5),
                Change::added('2', true)
                    ->onFile('foo.php')
                    ->onLine(10)
                    ->onColumn(5),
                Change::added('3', true)
                    ->onFile('foo.php')
                    ->onLine(10)
                    ->onColumn(5),
            ),
            $multiCheck($from, $to),
        );
    }
}
