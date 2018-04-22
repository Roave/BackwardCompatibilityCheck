<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\TraitBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\ClassBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\TraitBased\UseClassBasedChecksOnATrait;
use Roave\BetterReflection\Reflection\ReflectionClass;

/**
 * @covers \Roave\ApiCompare\Comparator\BackwardsCompatibility\TraitBased\UseClassBasedChecksOnATrait
 */
final class UseClassBasedChecksOnATraitTest extends TestCase
{
    public function testCompare() : void
    {
        $changes = Changes::fromArray([Change::added(uniqid('foo', true), true)]);

        /** @var $classBased ClassBased|MockObject */
        $classBased = $this->createMock(ClassBased::class);
        /** @var $fromTrait ReflectionClass|MockObject */
        $fromTrait = $this->createMock(ReflectionClass::class);
        /** @var $toTrait ReflectionClass|MockObject */
        $toTrait = $this->createMock(ReflectionClass::class);

        $classBased
            ->expects(self::once())
            ->method('compare')
            ->with($fromTrait, $toTrait)
            ->willReturn($changes);

        self::assertSame($changes, (new UseClassBasedChecksOnATrait($classBased))->compare($fromTrait, $toTrait));
    }
}
