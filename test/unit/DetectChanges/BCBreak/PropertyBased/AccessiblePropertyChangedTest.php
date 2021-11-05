<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\AccessiblePropertyChanged;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyBased;
use Roave\BetterReflection\Reflection\ReflectionProperty;

use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\AccessiblePropertyChanged
 */
final class AccessiblePropertyChangedTest extends TestCase
{
    /** @var PropertyBased&MockObject */
    private PropertyBased $check;

    /** @var ReflectionProperty&MockObject */
    private ReflectionProperty $fromProperty;

    /** @var ReflectionProperty&MockObject */
    private ReflectionProperty $toProperty;

    private AccessiblePropertyChanged $accessiblePropertyChanged;

    protected function setUp(): void
    {
        parent::setUp();

        $this->check                     = $this->createMock(PropertyBased::class);
        $this->accessiblePropertyChanged = new AccessiblePropertyChanged($this->check);
        $this->fromProperty              = $this->createMock(ReflectionProperty::class);
        $this->toProperty                = $this->createMock(ReflectionProperty::class);
    }

    public function testSkipsPrivateProperty(): void
    {
        $this
            ->check
            ->expects(self::never())
            ->method('__invoke');

        $this
            ->fromProperty
            ->method('isPrivate')
            ->willReturn(true);

        self::assertEquals(
            Changes::empty(),
            ($this->accessiblePropertyChanged)($this->fromProperty, $this->toProperty)
        );
    }

    public function testChecksAccessibleProperty(): void
    {
        $changes = Changes::fromList(Change::changed(uniqid('potato', true), true));

        $this
            ->check
            ->expects(self::atLeastOnce())
            ->method('__invoke')
            ->with($this->fromProperty, $this->toProperty)
            ->willReturn($changes);

        $this
            ->fromProperty
            ->method('isPrivate')
            ->willReturn(false);

        self::assertEquals(
            $changes,
            ($this->accessiblePropertyChanged)($this->fromProperty, $this->toProperty)
        );
    }
}
