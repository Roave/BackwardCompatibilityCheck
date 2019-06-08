<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\OnlyProtectedPropertyChanged;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyBased;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\OnlyProtectedPropertyChanged
 */
final class OnlyProtectedPropertyChangedTest extends TestCase
{
    /** @var PropertyBased&MockObject */
    private $check;

    /** @var ReflectionProperty&MockObject */
    private $fromProperty;

    /** @var ReflectionProperty&MockObject */
    private $toProperty;

    /** @var OnlyProtectedPropertyChanged */
    private $changed;

    protected function setUp() : void
    {
        parent::setUp();

        $this->check        = $this->createMock(PropertyBased::class);
        $this->changed      = new OnlyProtectedPropertyChanged($this->check);
        $this->fromProperty = $this->createMock(ReflectionProperty::class);
        $this->toProperty   = $this->createMock(ReflectionProperty::class);
    }

    public function testSkipsNonProtectedProperty() : void
    {
        $this
            ->check
            ->expects(self::never())
            ->method('__invoke');

        $this
            ->fromProperty
            ->expects(self::any())
            ->method('isProtected')
            ->willReturn(false);

        self::assertEquals(
            Changes::empty(),
            $this->changed->__invoke($this->fromProperty, $this->toProperty)
        );
    }

    public function testChecksProtectedProperty() : void
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
            ->expects(self::any())
            ->method('isProtected')
            ->willReturn(true);

        self::assertEquals(
            $changes,
            $this->changed->__invoke($this->fromProperty, $this->toProperty)
        );
    }
}
