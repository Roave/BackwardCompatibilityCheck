<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\DetectChanges\BCBreak\MethodBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\DetectChanges\BCBreak\MethodBased\MethodBased;
use Roave\ApiCompare\DetectChanges\BCBreak\MethodBased\OnlyPublicMethodChanged;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use function uniqid;

/**
 * @covers \Roave\ApiCompare\DetectChanges\BCBreak\MethodBased\OnlyPublicMethodChanged
 */
final class OnlyPublicMethodChangedTest extends TestCase
{
    /** @var MethodBased|MockObject */
    private $check;

    /** @var OnlyPublicMethodChanged */
    private $methodCheck;

    protected function setUp() : void
    {
        parent::setUp();

        $this->check       = $this->createMock(MethodBased::class);
        $this->methodCheck = new OnlyPublicMethodChanged($this->check);
    }

    public function testWillSkipCheckingNonPublicMethods() : void
    {
        /** @var ReflectionMethod|MockObject $to */
        $from = $this->createMock(ReflectionMethod::class);
        /** @var ReflectionMethod|MockObject $from */
        $to = $this->createMock(ReflectionMethod::class);

        $from
            ->expects(self::any())
            ->method('isPublic')
            ->willReturn(false);

        $this
            ->check
            ->expects(self::never())
            ->method('__invoke');

        self::assertEquals(Changes::empty(), $this->methodCheck->__invoke($from, $to));
    }

    public function testWillCheckPublicMethods() : void
    {
        /** @var ReflectionMethod|MockObject $to */
        $from = $this->createMock(ReflectionMethod::class);
        /** @var ReflectionMethod|MockObject $from */
        $to = $this->createMock(ReflectionMethod::class);

        $from
            ->expects(self::any())
            ->method('isPublic')
            ->willReturn(true);

        $result = Changes::fromList(Change::changed(uniqid('foo', true), true));

        $this
            ->check
            ->expects(self::any())
            ->method('__invoke')
            ->with($from, $to)
            ->willReturn($result);

        self::assertEquals($result, $this->methodCheck->__invoke($from, $to));
    }
}
