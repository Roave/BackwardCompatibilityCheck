<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ClassBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\OpenClassChanged;
use Roave\BetterReflection\Reflection\ReflectionClass;

use function uniqid;

/** @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\OpenClassChanged */
final class OpenClassChangedTest extends TestCase
{
    /** @var ClassBased&MockObject */
    private ClassBased $check;

    private OpenClassChanged $openClassChanged;

    /** @var ReflectionClass&MockObject */
    private ReflectionClass $fromClass;

    /** @var ReflectionClass&MockObject */
    private ReflectionClass $toClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->check            = $this->createMock(ClassBased::class);
        $this->openClassChanged = new OpenClassChanged($this->check);
        $this->fromClass        = $this->createMock(ReflectionClass::class);
        $this->toClass          = $this->createMock(ReflectionClass::class);
    }

    public function testWillCheckFinalClass(): void
    {
        $changes = Changes::fromList(Change::added(uniqid('carrot', true), true));

        $this
            ->fromClass
            ->method('isFinal')
            ->willReturn(false);

        $this
            ->check
            ->expects(self::atLeastOnce())
            ->method('__invoke')
            ->with($this->fromClass, $this->toClass)
            ->willReturn($changes);

        self::assertEquals($changes, ($this->openClassChanged)($this->fromClass, $this->toClass));
    }

    public function testWillNotCheckOpenClass(): void
    {
        $this
            ->fromClass
            ->method('isFinal')
            ->willReturn(true);

        $this
            ->check
            ->expects(self::never())
            ->method('__invoke');

        self::assertEquals(Changes::empty(), ($this->openClassChanged)($this->fromClass, $this->toClass));
    }
}
