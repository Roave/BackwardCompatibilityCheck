<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\AccessibleMethodChange;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodBased;
use Roave\BetterReflection\Reflection\ReflectionMethod;

use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\AccessibleMethodChange
 */
final class AccessibleMethodChangeTest extends TestCase
{
    /** @var MethodBased&MockObject */
    private MethodBased $check;

    private MethodBased $methodCheck;

    protected function setUp(): void
    {
        parent::setUp();

        $this->check       = $this->createMock(MethodBased::class);
        $this->methodCheck = new AccessibleMethodChange($this->check);
    }

    public function testWillSkipCheckingPrivateMethods(): void
    {
        $from = $this->createMock(ReflectionMethod::class);
        $to   = $this->createMock(ReflectionMethod::class);

        $from
            ->expects(self::any())
            ->method('isPrivate')
            ->willReturn(true);

        $this
            ->check
            ->expects(self::never())
            ->method('__invoke');

        self::assertEquals(Changes::empty(), $this->methodCheck->__invoke($from, $to));
    }

    public function testWillCheckVisibleMethods(): void
    {
        $from = $this->createMock(ReflectionMethod::class);
        $to   = $this->createMock(ReflectionMethod::class);

        $from
            ->expects(self::any())
            ->method('isPrivate')
            ->willReturn(false);

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
