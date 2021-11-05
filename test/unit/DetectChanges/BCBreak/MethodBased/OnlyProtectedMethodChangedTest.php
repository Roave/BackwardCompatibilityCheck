<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\OnlyProtectedMethodChanged;
use Roave\BetterReflection\Reflection\ReflectionMethod;

use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\OnlyProtectedMethodChanged
 */
final class OnlyProtectedMethodChangedTest extends TestCase
{
    /** @var MethodBased&MockObject */
    private MethodBased $check;

    private OnlyProtectedMethodChanged $methodCheck;

    protected function setUp(): void
    {
        parent::setUp();

        $this->check       = $this->createMock(MethodBased::class);
        $this->methodCheck = new OnlyProtectedMethodChanged($this->check);
    }

    public function testWillSkipCheckingNonProtectedMethods(): void
    {
        $from = $this->createMock(ReflectionMethod::class);
        $to   = $this->createMock(ReflectionMethod::class);

        $from
            ->method('isProtected')
            ->willReturn(false);

        $this
            ->check
            ->expects(self::never())
            ->method('__invoke');

        self::assertEquals(Changes::empty(), $this->methodCheck->__invoke($from, $to));
    }

    public function testWillCheckProtectedMethods(): void
    {
        $from = $this->createMock(ReflectionMethod::class);
        $to   = $this->createMock(ReflectionMethod::class);

        $from
            ->method('isProtected')
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
