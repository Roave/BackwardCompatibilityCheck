<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased\ClassConstantBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased\SkipClassConstantBasedErrors;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased\SkipClassConstantBasedErrors
 */
final class SkipClassConstantBasedErrorsTest extends TestCase
{
    /** @var ClassConstantBased&MockObject */
    private ClassConstantBased $next;

    private SkipClassConstantBasedErrors $check;

    protected function setUp() : void
    {
        $this->next  = $this->createMock(ClassConstantBased::class);
        $this->check = new SkipClassConstantBasedErrors($this->next);
    }

    public function testWillForwardChecks() : void
    {
        $fromConstant    = $this->createMock(ReflectionClassConstant::class);
        $toConstant      = $this->createMock(ReflectionClassConstant::class);
        $expectedChanges = Changes::fromList(Change::added(
            uniqid('foo', true),
            true
        ));

        $this
            ->next
            ->expects(self::once())
            ->method('__invoke')
            ->with($fromConstant, $toConstant)
            ->willReturn($expectedChanges);

        self::assertEquals($expectedChanges, $this->check->__invoke($fromConstant, $toConstant));
    }

    public function testWillCollectFailures() : void
    {
        $fromConstant = $this->createMock(ReflectionClassConstant::class);
        $toConstant   = $this->createMock(ReflectionClassConstant::class);
        $exception    = new Exception();

        $this
            ->next
            ->expects(self::once())
            ->method('__invoke')
            ->with($fromConstant, $toConstant)
            ->willThrowException($exception);

        self::assertEquals(
            Changes::fromList(Change::skippedDueToFailure($exception)),
            $this->check->__invoke($fromConstant, $toConstant)
        );
    }
}
