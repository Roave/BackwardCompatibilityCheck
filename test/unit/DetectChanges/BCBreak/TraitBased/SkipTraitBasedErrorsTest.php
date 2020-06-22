<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\TraitBased;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased\SkipTraitBasedErrors;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased\TraitBased;
use Roave\BetterReflection\Reflection\ReflectionClass;

use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased\SkipTraitBasedErrors
 */
final class SkipTraitBasedErrorsTest extends TestCase
{
    /** @var TraitBased&MockObject */
    private TraitBased $next;

    private SkipTraitBasedErrors $check;

    protected function setUp(): void
    {
        $this->next  = $this->createMock(TraitBased::class);
        $this->check = new SkipTraitBasedErrors($this->next);
    }

    public function testWillForwardChecks(): void
    {
        $fromTrait       = $this->createMock(ReflectionClass::class);
        $toTrait         = $this->createMock(ReflectionClass::class);
        $expectedChanges = Changes::fromList(Change::added(
            uniqid('foo', true),
            true
        ));

        $this
            ->next
            ->expects(self::once())
            ->method('__invoke')
            ->with($fromTrait, $toTrait)
            ->willReturn($expectedChanges);

        self::assertEquals($expectedChanges, $this->check->__invoke($fromTrait, $toTrait));
    }

    public function testWillCollectFailures(): void
    {
        $fromTrait = $this->createMock(ReflectionClass::class);
        $toTrait   = $this->createMock(ReflectionClass::class);
        $exception = new Exception();

        $this
            ->next
            ->expects(self::once())
            ->method('__invoke')
            ->with($fromTrait, $toTrait)
            ->willThrowException($exception);

        self::assertEquals(
            Changes::fromList(Change::skippedDueToFailure($exception)),
            $this->check->__invoke($fromTrait, $toTrait)
        );
    }
}
