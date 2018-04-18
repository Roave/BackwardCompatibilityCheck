<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\ClassBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\FinalClassChanged;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\OpenClassChanged;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function uniqid;

/**
 * @covers \Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\OpenClassChanged
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
        $changes = Changes::fromArray([Change::added(uniqid('carrot', true), true)]);

        $this
            ->fromClass
            ->expects(self::any())
            ->method('isFinal')
            ->willReturn(false);

        $this
            ->check
            ->expects(self::atLeastOnce())
            ->method('compare')
            ->with($this->fromClass, $this->toClass)
            ->willReturn($changes);

        self::assertEquals($changes, $this->openClassChanged->compare($this->fromClass, $this->toClass));
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
            ->method('compare');

        self::assertEquals(Changes::new(), $this->openClassChanged->compare($this->fromClass, $this->toClass));
    }
}
