<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\ClassBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased\FunctionBased;

/**
 * @covers \Roave\ApiCompare\Comparator
 */
final class ComparatorTest extends TestCase
{
    /** @var StringReflectorFactory|null */
    private static $stringReflectorFactory;

    /** @var ClassBased|MockObject */
    private $classBasedComparison;

    /** @var FunctionBased|MockObject */
    private $functionBasedComparison;

    /** @var Comparator */
    private $comparator;

    public static function setUpBeforeClass() : void
    {
        self::$stringReflectorFactory = new StringReflectorFactory();
    }

    protected function setUp() : void
    {
        parent::setUp();

        $this->classBasedComparison    = $this->createMock(ClassBased::class);
        $this->functionBasedComparison = $this->createMock(FunctionBased::class);
        $this->comparator              = new Comparator($this->classBasedComparison, $this->functionBasedComparison);
    }

    /**
     * @param mixed $expected
     * @param mixed $actual
     */
    private static function assertEqualsIgnoringOrder($expected, $actual) : void
    {
        self::assertEquals($expected, $actual, '', 0.0, 10, true);
    }

    public function testRemovingAClassCausesABreak() : void
    {
        $this->classBasedComparatorWillNotBeCalled();
        $this->functionBasedComparatorWillNotBeCalled();

        self::assertEqualsIgnoringOrder(
            Changes::fromArray([
                Change::removed('Class A has been deleted', true),
            ]),
            $this->comparator->compare(
                self::$stringReflectorFactory->__invoke('<?php class A { private function foo() {} }'),
                self::$stringReflectorFactory->__invoke('<?php ')
            )
        );
    }

    public function testRemovingAPrivateMethodDoesNotCauseBreak() : void
    {
        $this->classBasedComparatorWillBeCalled();
        $this->functionBasedComparatorWillNotBeCalled();

        self::assertEqualsIgnoringOrder(
            Changes::fromArray([
                Change::changed('class change', true),
            ]),
            $this->comparator->compare(
                self::$stringReflectorFactory->__invoke('<?php class A { private function foo() {} }'),
                self::$stringReflectorFactory->__invoke('<?php class A { }')
            )
        );
    }

    public function testRenamingParametersDoesNotCauseBcBreak() : void
    {
        $this->classBasedComparatorWillBeCalled();
        $this->functionBasedComparatorWillBeCalled();

        self::assertEqualsIgnoringOrder(
            Changes::fromArray([
                Change::changed('class change', true),
                Change::changed('function change', true),
            ]),
            $this->comparator->compare(
                self::$stringReflectorFactory->__invoke('<?php class A { function foo(int $a, string $b) {} }'),
                self::$stringReflectorFactory->__invoke('<?php class A { function foo(int $b, string $a) {} }')
            )
        );
    }

    public function testMakingAClassFinal() : void
    {
        $this->classBasedComparatorWillBeCalled();
        $this->functionBasedComparatorWillNotBeCalled();

        self::assertEqualsIgnoringOrder(
            Changes::fromArray([
                Change::changed('class change', true),
                Change::changed('Class A is now final', true),
            ]),
            $this->comparator->compare(
                self::$stringReflectorFactory->__invoke('<?php class A { }'),
                self::$stringReflectorFactory->__invoke('<?php final class A { }')
            )
        );
    }

    private function classBasedComparatorWillBeCalled() : void
    {
        $this
            ->classBasedComparison
            ->expects(self::atLeastOnce())
            ->method('compare')
            ->willReturn(Changes::fromArray([
                Change::changed('class change', true)
            ]));
    }

    private function classBasedComparatorWillNotBeCalled() : void
    {
        $this
            ->classBasedComparison
            ->expects(self::never())
            ->method('compare');
    }

    private function functionBasedComparatorWillBeCalled() : void
    {
        $this
            ->functionBasedComparison
            ->expects(self::atLeastOnce())
            ->method('compare')
            ->willReturn(Changes::fromArray([
                Change::changed('function change', true)
            ]));
    }

    private function functionBasedComparatorWillNotBeCalled() : void
    {
        $this
            ->functionBasedComparison
            ->expects(self::never())
            ->method('compare');
    }
}
