<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ClassBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\UseClassBasedChecksOnAnInterface;
use Roave\BetterReflection\Reflection\ReflectionClass;

use function uniqid;

/** @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\UseClassBasedChecksOnAnInterface */
final class UseClassBasedChecksOnAnInterfaceTest extends TestCase
{
    public function testCompare(): void
    {
        $changes = Changes::fromList(Change::added(uniqid('foo', true), true));

        $classBased    = $this->createMock(ClassBased::class);
        $fromInterface = $this->createMock(ReflectionClass::class);
        $toInterface   = $this->createMock(ReflectionClass::class);

        $classBased
            ->expects(self::once())
            ->method('__invoke')
            ->with($fromInterface, $toInterface)
            ->willReturn($changes);

        self::assertSame(
            $changes,
            (new UseClassBasedChecksOnAnInterface($classBased))($fromInterface, $toInterface),
        );
    }
}
