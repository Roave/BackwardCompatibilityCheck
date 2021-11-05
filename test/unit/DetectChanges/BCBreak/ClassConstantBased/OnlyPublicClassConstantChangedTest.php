<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased\ClassConstantBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased\OnlyPublicClassConstantChanged;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased\OnlyPublicClassConstantChanged
 */
final class OnlyPublicClassConstantChangedTest extends TestCase
{
    /** @var ClassConstantBased&MockObject */
    private ClassConstantBased $check;

    /** @var ReflectionClassConstant&MockObject */
    private ReflectionClassConstant $fromConstant;

    /** @var ReflectionClassConstant&MockObject */
    private ReflectionClassConstant $toConstant;

    private OnlyPublicClassConstantChanged $changed;

    protected function setUp(): void
    {
        parent::setUp();

        $this->check        = $this->createMock(ClassConstantBased::class);
        $this->changed      = new OnlyPublicClassConstantChanged($this->check);
        $this->fromConstant = $this->createMock(ReflectionClassConstant::class);
        $this->toConstant   = $this->createMock(ReflectionClassConstant::class);
    }

    public function testSkipsNonPublicConstant(): void
    {
        $this
            ->check
            ->expects(self::never())
            ->method('__invoke');

        $this
            ->fromConstant
            ->method('isPublic')
            ->willReturn(false);

        self::assertEquals(
            Changes::empty(),
            $this->changed->__invoke($this->fromConstant, $this->toConstant)
        );
    }

    public function testChecksPublicConstant(): void
    {
        $changes = Changes::fromList(Change::changed(uniqid('potato', true), true));

        $this
            ->check
            ->expects(self::atLeastOnce())
            ->method('__invoke')
            ->with($this->fromConstant, $this->toConstant)
            ->willReturn($changes);

        $this
            ->fromConstant
            ->method('isPublic')
            ->willReturn(true);

        self::assertEquals(
            $changes,
            $this->changed->__invoke($this->fromConstant, $this->toConstant)
        );
    }
}
