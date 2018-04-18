<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\MethodBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased\FunctionBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\MethodBased\MethodBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\MethodBased\MethodFunctionDefinitionChanged;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use function uniqid;

/**
 * @covers \Roave\ApiCompare\Comparator\BackwardsCompatibility\MethodBased\MethodFunctionDefinitionChanged
 */
final class MethodFunctionDefinitionChangedTest extends TestCase
{
    /** @var FunctionBased|MockObject */
    private $functionCheck;

    /** @var MethodBased */
    private $methodCheck;

    protected function setUp() : void
    {
        parent::setUp();

        $this->functionCheck = $this->createMock(FunctionBased::class);
        $this->methodCheck   = new MethodFunctionDefinitionChanged($this->functionCheck);
    }

    public function testWillCheckVisibleMethods() : void
    {
        /** @var ReflectionMethod|MockObject $to */
        $from = $this->createMock(ReflectionMethod::class);
        /** @var ReflectionMethod|MockObject $from */
        $to = $this->createMock(ReflectionMethod::class);

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
