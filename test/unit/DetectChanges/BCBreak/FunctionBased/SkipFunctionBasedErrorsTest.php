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
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\SkipFunctionBasedErrors
 */
final class SkipFunctionBasedErrorsTest extends TestCase
{
    /** @var FunctionBased|MockObject */
    private $next;

    /** @var SkipFunctionBasedErrors */
    private $check;

    protected function setUp() : void
    {
        $this->next  = $this->createMock(FunctionBased::class);
        $this->check = new SkipFunctionBasedErrors($this->next);
    }

    public function testWillForwardChecks() : void
    {
        $fromFunction    = $this->createMock(ReflectionFunctionAbstract::class);
        $toFunction      = $this->createMock(ReflectionFunctionAbstract::class);
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

        self::assertEquals($expectedChanges, $this->check->__invoke($fromFunction, $toFunction));
    }

    public function testWillCollectFailures() : void
    {
        $fromFunction = $this->createMock(ReflectionFunctionAbstract::class);
        $toFunction   = $this->createMock(ReflectionFunctionAbstract::class);
        $exception    = new Exception();

        $this
            ->next
            ->expects(self::once())
            ->method('__invoke')
            ->with($fromFunction, $toFunction)
            ->willThrowException($exception);

        self::assertEquals(
            Changes::fromList(Change::skippedDueToFailure($exception)),
            $this->check->__invoke($fromFunction, $toFunction)
        );
    }
}
