<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\InterfaceBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\SkipInterfaceBasedErrors;
use Roave\BetterReflection\Reflection\ReflectionClass;

use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\SkipInterfaceBasedErrors
 */
final class SkipInterfaceBasedErrorsTest extends TestCase
{
    /** @var InterfaceBased&MockObject */
    private InterfaceBased $next;

    private SkipInterfaceBasedErrors $check;

    protected function setUp(): void
    {
        $this->next  = $this->createMock(InterfaceBased::class);
        $this->check = new SkipInterfaceBasedErrors($this->next);
    }

    public function testWillForwardChecks(): void
    {
        $fromInterface   = $this->createMock(ReflectionClass::class);
        $toInterface     = $this->createMock(ReflectionClass::class);
        $expectedChanges = Changes::fromList(Change::added(
            uniqid('foo', true),
            true
        ));

        $this
            ->next
            ->expects(self::once())
            ->method('__invoke')
            ->with($fromInterface, $toInterface)
            ->willReturn($expectedChanges);

        self::assertEquals($expectedChanges, ($this->check)($fromInterface, $toInterface));
    }

    public function testWillCollectFailures(): void
    {
        $fromInterface = $this->createMock(ReflectionClass::class);
        $toInterface   = $this->createMock(ReflectionClass::class);
        $exception     = new Exception();

        $this
            ->next
            ->expects(self::once())
            ->method('__invoke')
            ->with($fromInterface, $toInterface)
            ->willThrowException($exception);

        self::assertEquals(
            Changes::fromList(Change::skippedDueToFailure($exception)),
            ($this->check)($fromInterface, $toInterface)
        );
    }
}
