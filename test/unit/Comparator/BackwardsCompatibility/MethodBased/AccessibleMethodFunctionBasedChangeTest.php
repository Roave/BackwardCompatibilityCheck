<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\MethodBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased\FunctionBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\MethodBased\AccessibleMethodFunctionBasedChange;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\MethodBased\MethodBased;
use Roave\BetterReflection\Reflection\ReflectionMethod;

final class AccessibleMethodFunctionBasedChangeTest extends TestCase
{
    /** @var FunctionBased|MockObject */
    private $functionCheck;

    /** @var MethodBased */
    private $methodCheck;

    protected function setUp()
    {
        parent::setUp();

        $this->functionCheck = $this->createMock(FunctionBased::class);
        $this->methodCheck   = new AccessibleMethodFunctionBasedChange($this->functionCheck);
    }

    public function testWillSkipCheckingPrivateMethods() : void
    {
        /** @var ReflectionMethod|MockObject $to */
        $from = $this->createMock(ReflectionMethod::class);
        /** @var ReflectionMethod|MockObject $from */
        $to = $this->createMock(ReflectionMethod::class);

        $from
            ->expects(self::any())
            ->method('isPrivate')
            ->willReturn(true);

        $this
            ->functionCheck
            ->expects(self::never())
            ->method('compare');

        self::assertEquals(Changes::new(), $this->methodCheck->compare($from, $to));
    }

    public function testWillCheckVisibleMethods() : void
    {
        /** @var ReflectionMethod|MockObject $to */
        $from = $this->createMock(ReflectionMethod::class);
        /** @var ReflectionMethod|MockObject $from */
        $to = $this->createMock(ReflectionMethod::class);

        $from
            ->expects(self::any())
            ->method('isPrivate')
            ->willReturn(false);

        $result = Changes::fromArray([
            Change::changed(uniqid('foo', true), true),
        ]);

        $this
            ->functionCheck
            ->expects(self::any())
            ->method('compare')
            ->with($from, $to)
            ->willReturn($result);

        self::assertEquals($result, $this->methodCheck->compare($from, $to));
    }
}
