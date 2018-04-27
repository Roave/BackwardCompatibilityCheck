<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\DetectChanges\BCBreak\ClassBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\DetectChanges\BCBreak\ClassBased\ClassBased;
use Roave\ApiCompare\DetectChanges\BCBreak\ClassBased\FinalClassChanged;
use Roave\ApiCompare\DetectChanges\BCBreak\ClassBased\OpenClassChanged;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function uniqid;

/**
 * @covers \Roave\ApiCompare\DetectChanges\BCBreak\ClassBased\OpenClassChanged
 */
final class OpenClassChangedTest extends TestCase
{
    /** @var ClassBased|MockObject */
    private $check;

    /** @var FinalClassChanged */
    private $openClassChanged;

    /** @var ReflectionClass|MockObject */
    private $fromClass;

    /** @var ReflectionClass|MockObject */
    private $toClass;

    protected function setUp() : void
    {
        parent::setUp();

        $this->check            = $this->createMock(ClassBased::class);
        $this->openClassChanged = new OpenClassChanged($this->check);
        $this->fromClass        = $this->createMock(ReflectionClass::class);
        $this->toClass          = $this->createMock(ReflectionClass::class);
    }

    public function testWillCheckFinalClass() : void
    {
        $changes = Changes::fromList(Change::added(uniqid('carrot', true), true));

        $this
            ->fromClass
            ->expects(self::any())
            ->method('isFinal')
            ->willReturn(false);

        $this
            ->check
            ->expects(self::atLeastOnce())
            ->method('__invoke')
            ->with($this->fromClass, $this->toClass)
            ->willReturn($changes);

        self::assertEquals($changes, $this->openClassChanged->__invoke($this->fromClass, $this->toClass));
    }

    public function testWillNotCheckOpenClass() : void
    {
        $this
            ->fromClass
            ->expects(self::any())
            ->method('isFinal')
            ->willReturn(true);

        $this
            ->check
            ->expects(self::never())
            ->method('__invoke');

        self::assertEquals(Changes::empty(), $this->openClassChanged->__invoke($this->fromClass, $this->toClass));
    }
}
