<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\SkipMethodBasedErrors;
use Roave\BetterReflection\Reflection\ReflectionMethod;

use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\SkipMethodBasedErrors
 */
final class SkipMethodBasedErrorsTest extends TestCase
{
    /** @var MethodBased&MockObject */
    private MethodBased $next;

    private SkipMethodBasedErrors $check;

    protected function setUp(): void
    {
        $this->next  = $this->createMock(MethodBased::class);
        $this->check = new SkipMethodBasedErrors($this->next);
    }

    public function testWillForwardChecks(): void
    {
        $fromMethod      = $this->createMock(ReflectionMethod::class);
        $toMethod        = $this->createMock(ReflectionMethod::class);
        $expectedChanges = Changes::fromList(Change::added(
            uniqid('foo', true),
            true
        ));

        $this
            ->next
            ->expects(self::once())
            ->method('__invoke')
            ->with($fromMethod, $toMethod)
            ->willReturn($expectedChanges);

        self::assertEquals($expectedChanges, $this->check->__invoke($fromMethod, $toMethod));
    }

    public function testWillCollectFailures(): void
    {
        $fromMethod = $this->createMock(ReflectionMethod::class);
        $toMethod   = $this->createMock(ReflectionMethod::class);
        $exception  = new Exception();

        $this
            ->next
            ->expects(self::once())
            ->method('__invoke')
            ->with($fromMethod, $toMethod)
            ->willThrowException($exception);

        self::assertEquals(
            Changes::fromList(Change::skippedDueToFailure($exception)),
            $this->check->__invoke($fromMethod, $toMethod)
        );
    }
}
