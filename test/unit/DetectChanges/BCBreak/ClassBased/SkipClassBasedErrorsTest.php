<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ClassBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\SkipClassBasedErrors;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\SkipClassBasedErrors
 */
final class SkipClassBasedErrorsTest extends TestCase
{
    /** @var ClassBased&MockObject */
    private $next;

    /** @var SkipClassBasedErrors */
    private $check;

    protected function setUp() : void
    {
        $this->next  = $this->createMock(ClassBased::class);
        $this->check = new SkipClassBasedErrors($this->next);
    }

    public function testWillForwardChecks() : void
    {
        $fromClass       = $this->createMock(ReflectionClass::class);
        $toClass         = $this->createMock(ReflectionClass::class);
        $expectedChanges = Changes::fromList(Change::added(
            uniqid('foo', true),
            true
        ));

        $this
            ->next
            ->expects(self::once())
            ->method('__invoke')
            ->with($fromClass, $toClass)
            ->willReturn($expectedChanges);

        self::assertEquals($expectedChanges, $this->check->__invoke($fromClass, $toClass));
    }

    public function testWillCollectFailures() : void
    {
        $fromClass = $this->createMock(ReflectionClass::class);
        $toClass   = $this->createMock(ReflectionClass::class);
        $exception = new Exception();

        $this
            ->next
            ->expects(self::once())
            ->method('__invoke')
            ->with($fromClass, $toClass)
            ->willThrowException($exception);

        self::assertEquals(
            Changes::fromList(Change::skippedDueToFailure($exception)),
            $this->check->__invoke($fromClass, $toClass)
        );
    }
}
