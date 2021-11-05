<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\SkipPropertyBasedErrors;
use Roave\BetterReflection\Reflection\ReflectionProperty;

use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\SkipPropertyBasedErrors
 */
final class SkipPropertyBasedErrorsTest extends TestCase
{
    /** @var PropertyBased&MockObject */
    private PropertyBased $next;

    private SkipPropertyBasedErrors $check;

    protected function setUp(): void
    {
        $this->next  = $this->createMock(PropertyBased::class);
        $this->check = new SkipPropertyBasedErrors($this->next);
    }

    public function testWillForwardChecks(): void
    {
        $fromProperty    = $this->createMock(ReflectionProperty::class);
        $toProperty      = $this->createMock(ReflectionProperty::class);
        $expectedChanges = Changes::fromList(Change::added(
            uniqid('foo', true),
            true
        ));

        $this
            ->next
            ->expects(self::once())
            ->method('__invoke')
            ->with($fromProperty, $toProperty)
            ->willReturn($expectedChanges);

        self::assertEquals($expectedChanges, ($this->check)($fromProperty, $toProperty));
    }

    public function testWillCollectFailures(): void
    {
        $fromProperty = $this->createMock(ReflectionProperty::class);
        $toProperty   = $this->createMock(ReflectionProperty::class);
        $exception    = new Exception();

        $this
            ->next
            ->expects(self::once())
            ->method('__invoke')
            ->with($fromProperty, $toProperty)
            ->willThrowException($exception);

        self::assertEquals(
            Changes::fromList(Change::skippedDueToFailure($exception)),
            ($this->check)($fromProperty, $toProperty)
        );
    }
}
