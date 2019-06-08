<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ClassBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\UseClassBasedChecksOnAnInterface;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\UseClassBasedChecksOnAnInterface
 */
final class UseClassBasedChecksOnAnInterfaceTest extends TestCase
{
    public function testCompare() : void
    {
        $changes = Changes::fromList(Change::added(uniqid('foo', true), true));

        /** @var ClassBased&MockObject $classBased */
        $classBased = $this->createMock(ClassBased::class);
        /** @var ReflectionClass&MockObject $fromInterface */
        $fromInterface = $this->createMock(ReflectionClass::class);
        /** @var ReflectionClass&MockObject $toInterface */
        $toInterface = $this->createMock(ReflectionClass::class);

        $classBased
            ->expects(self::once())
            ->method('__invoke')
            ->with($fromInterface, $toInterface)
            ->willReturn($changes);

        self::assertSame(
            $changes,
            (new UseClassBasedChecksOnAnInterface($classBased))->__invoke($fromInterface, $toInterface)
        );
    }
}
