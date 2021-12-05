<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\FunctionBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\SkipFunctionBasedErrors;

use Roave\BetterReflection\Reflection\ReflectionFunction;
use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\SkipFunctionBasedErrors
 */
final class SkipFunctionBasedErrorsTest extends TestCase
{
    /** @var FunctionBased&MockObject */
    private FunctionBased $next;

    private SkipFunctionBasedErrors $check;

    protected function setUp(): void
    {
        $this->next  = $this->createMock(FunctionBased::class);
        $this->check = new SkipFunctionBasedErrors($this->next);
    }

    public function testWillForwardChecks(): void
    {
        $fromFunction    = $this->createMock(ReflectionFunction::class);
        $toFunction      = $this->createMock(ReflectionFunction::class);
        $expectedChanges = Changes::fromList(Change::added(
            uniqid('foo', true),
            true
        ));

        $this
            ->next
            ->expects(self::once())
            ->method('__invoke')
            ->with($fromFunction, $toFunction)
            ->willReturn($expectedChanges);

        self::assertEquals($expectedChanges, ($this->check)($fromFunction, $toFunction));
    }

    public function testWillCollectFailures(): void
    {
        $fromFunction = $this->createMock(ReflectionFunction::class);
        $toFunction   = $this->createMock(ReflectionFunction::class);
        $exception    = new Exception();

        $this
            ->next
            ->expects(self::once())
            ->method('__invoke')
            ->with($fromFunction, $toFunction)
            ->willThrowException($exception);

        self::assertEquals(
            Changes::fromList(Change::skippedDueToFailure($exception)),
            ($this->check)($fromFunction, $toFunction)
        );
    }
}
