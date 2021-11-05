<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\OnlyPublicMethodChanged;
use Roave\BetterReflection\Reflection\ReflectionMethod;

use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\OnlyPublicMethodChanged
 */
final class OnlyPublicMethodChangedTest extends TestCase
{
    /** @var MethodBased&MockObject */
    private MethodBased $check;

    private OnlyPublicMethodChanged $methodCheck;

    protected function setUp(): void
    {
        parent::setUp();

        $this->check       = $this->createMock(MethodBased::class);
        $this->methodCheck = new OnlyPublicMethodChanged($this->check);
    }

    public function testWillSkipCheckingNonPublicMethods(): void
    {
        $from = $this->createMock(ReflectionMethod::class);
        $to   = $this->createMock(ReflectionMethod::class);

        $from
            ->method('isPublic')
            ->willReturn(false);

        $this
            ->check
            ->expects(self::never())
            ->method('__invoke');

        self::assertEquals(Changes::empty(), $this->methodCheck->__invoke($from, $to));
    }

    public function testWillCheckPublicMethods(): void
    {
        $from = $this->createMock(ReflectionMethod::class);
        $to   = $this->createMock(ReflectionMethod::class);

        $from
            ->method('isPublic')
            ->willReturn(true);

        $result = Changes::fromList(Change::changed(uniqid('foo', true), true));

        $this
            ->check
            ->method('__invoke')
            ->with($from, $to)
            ->willReturn($result);

        self::assertEquals($result, $this->methodCheck->__invoke($from, $to));
    }
}
