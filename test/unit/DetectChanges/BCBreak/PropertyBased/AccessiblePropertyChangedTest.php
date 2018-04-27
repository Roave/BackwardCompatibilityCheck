<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\DetectChanges\BCBreak\PropertyBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\DetectChanges\BCBreak\PropertyBased\AccessiblePropertyChanged;
use Roave\ApiCompare\DetectChanges\BCBreak\PropertyBased\PropertyBased;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use function uniqid;

/**
 * @covers \Roave\ApiCompare\DetectChanges\BCBreak\PropertyBased\AccessiblePropertyChanged
 */
final class AccessiblePropertyChangedTest extends TestCase
{
    /** @var PropertyBased|MockObject */
    private $check;

    /** @var ReflectionProperty|MockObject */
    private $fromProperty;

    /** @var ReflectionProperty|MockObject */
    private $toProperty;

    /** @var AccessiblePropertyChanged */
    private $accessiblePropertyChanged;

    protected function setUp() : void
    {
        parent::setUp();

        $this->check                     = $this->createMock(PropertyBased::class);
        $this->accessiblePropertyChanged = new AccessiblePropertyChanged($this->check);
        $this->fromProperty              = $this->createMock(ReflectionProperty::class);
        $this->toProperty                = $this->createMock(ReflectionProperty::class);
    }

    public function testSkipsPrivateProperty() : void
    {
        $this
            ->check
            ->expects(self::never())
            ->method('__invoke');

        $this
            ->fromProperty
            ->expects(self::any())
            ->method('isPrivate')
            ->willReturn(true);

        self::assertEquals(
            Changes::empty(),
            $this->accessiblePropertyChanged->__invoke($this->fromProperty, $this->toProperty)
        );
    }

    public function testChecksAccessibleProperty() : void
    {
        $changes = Changes::fromArray([Change::changed(uniqid('potato', true), true)]);

        $this
            ->check
            ->expects(self::atLeastOnce())
            ->method('__invoke')
            ->with($this->fromProperty, $this->toProperty)
            ->willReturn($changes);

        $this
            ->fromProperty
            ->expects(self::any())
            ->method('isPrivate')
            ->willReturn(false);

        self::assertEquals(
            $changes,
            $this->accessiblePropertyChanged->__invoke($this->fromProperty, $this->toProperty)
        );
    }
}
