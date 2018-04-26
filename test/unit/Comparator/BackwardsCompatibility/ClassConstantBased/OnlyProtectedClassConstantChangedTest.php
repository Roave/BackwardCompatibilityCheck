<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\ClassConstantBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassConstantBased\ClassConstantBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassConstantBased\OnlyProtectedClassConstantChanged;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use function uniqid;

/**
 * @covers \Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassConstantBased\OnlyProtectedClassConstantChanged
 */
final class OnlyProtectedClassConstantChangedTest extends TestCase
{
    /** @var ClassConstantBased|MockObject */
    private $check;

    /** @var ReflectionClassConstant|MockObject */
    private $fromConstant;

    /** @var ReflectionClassConstant|MockObject */
    private $toConstant;

    /** @var OnlyProtectedClassConstantChanged */
    private $changed;

    protected function setUp() : void
    {
        parent::setUp();

        $this->check        = $this->createMock(ClassConstantBased::class);
        $this->changed      = new OnlyProtectedClassConstantChanged($this->check);
        $this->fromConstant = $this->createMock(ReflectionClassConstant::class);
        $this->toConstant   = $this->createMock(ReflectionClassConstant::class);
    }

    public function testSkipsNonProtectedConstant() : void
    {
        $this
            ->check
            ->expects(self::never())
            ->method('compare');

        $this
            ->fromConstant
            ->expects(self::any())
            ->method('isProtected')
            ->willReturn(false);

        self::assertEquals(
            Changes::new(),
            $this->changed->compare($this->fromConstant, $this->toConstant)
        );
    }

    public function testChecksProtectedConstant() : void
    {
        $changes = Changes::fromArray([Change::changed(uniqid('potato', true), true)]);

        $this
            ->check
            ->expects(self::atLeastOnce())
            ->method('compare')
            ->with($this->fromConstant, $this->toConstant)
            ->willReturn($changes);

        $this
            ->fromConstant
            ->expects(self::any())
            ->method('isProtected')
            ->willReturn(true);

        self::assertEquals(
            $changes,
            $this->changed->compare($this->fromConstant, $this->toConstant)
        );
    }
}
