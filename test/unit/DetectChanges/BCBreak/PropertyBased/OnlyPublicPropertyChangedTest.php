<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\OnlyPublicPropertyChanged;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyBased;
use Roave\BetterReflection\Reflection\ReflectionProperty;

use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\OnlyPublicPropertyChanged
 */
final class OnlyPublicPropertyChangedTest extends TestCase
{
    /** @var PropertyBased&MockObject */
    private PropertyBased $check;

    /** @var ReflectionProperty&MockObject */
    private ReflectionProperty $fromProperty;

    /** @var ReflectionProperty&MockObject */
    private ReflectionProperty $toProperty;

    private OnlyPublicPropertyChanged $changed;

    protected function setUp(): void
    {
        parent::setUp();

        $this->check        = $this->createMock(PropertyBased::class);
        $this->changed      = new OnlyPublicPropertyChanged($this->check);
        $this->fromProperty = $this->createMock(ReflectionProperty::class);
        $this->toProperty   = $this->createMock(ReflectionProperty::class);
    }

    public function testSkipsNonPublicProperty(): void
    {
        $this
            ->check
            ->expects(self::never())
            ->method('__invoke');

        $this
            ->fromProperty
            ->method('isPublic')
            ->willReturn(false);

        self::assertEquals(
            Changes::empty(),
            $this->changed->__invoke($this->fromProperty, $this->toProperty)
        );
    }

    public function testChecksPublicProperty(): void
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
            ->method('isPublic')
            ->willReturn(true);

        self::assertEquals(
            $changes,
            $this->changed->__invoke($this->fromProperty, $this->toProperty)
        );
    }
}
