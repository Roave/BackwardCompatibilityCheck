<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\ClassBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassConstantBased\ConstantBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\InterfaceBased\InterfaceBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\MethodBased\MethodBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased\PropertyBased;

/**
 * @covers \Roave\ApiCompare\Comparator
 */
final class ComparatorTest extends TestCase
{
    /** @var StringReflectorFactory|null */
    private static $stringReflectorFactory;

    /** @var ClassBased|MockObject */
    private $classBasedComparison;

    /** @var InterfaceBased|MockObject */
    private $interfaceBasedComparison;

    /** @var MethodBased|MockObject */
    private $methodBasedComparison;

    /** @var ConstantBased|MockObject */
    private $constantBasedComparison;

    /** @var Comparator */
    private $comparator;

    public static function setUpBeforeClass() : void
    {
        self::$stringReflectorFactory = new StringReflectorFactory();
    }

    protected function setUp() : void
    {
        parent::setUp();

        $this->classBasedComparison     = $this->createMock(ClassBased::class);
        $this->interfaceBasedComparison = $this->createMock(InterfaceBased::class);
        $this->methodBasedComparison    = $this->createMock(MethodBased::class);
        $this->constantBasedComparison  = $this->createMock(ConstantBased::class);
        $this->comparator               = new Comparator(
            $this->classBasedComparison,
            $this->interfaceBasedComparison,
            $this->methodBasedComparison,
            $this->constantBasedComparison
        );
    }

    public function testWillRunSubComparators() : void
    {
        $this->classBasedComparatorWillBeCalled();
        $this->methodBasedComparatorWillBeCalled();
        $this->constantBasedComparatorWillBeCalled();
        $this->interfaceBasedComparatorWillNotBeCalled();

        self::assertEqualsIgnoringOrder(
            Changes::fromArray([
                Change::changed('class change', true),
                Change::changed('constant change', true),
                Change::changed('method change', true),
            ]),
            $this->comparator->compare(
                self::$stringReflectorFactory->__invoke(
                    <<<'PHP'
<?php

class A {
    const A_CONSTANT = 'foo';
    public $aProperty;
    public function aMethod() {}
}
PHP
                ),
                self::$stringReflectorFactory->__invoke(
                    <<<'PHP'
<?php

class A {
    const A_CONSTANT = 'foo';
    public $aProperty;
    public function aMethod() {}
}
PHP
                )
            )
        );
    }

    public function testWillNotRunSubComparatorsIfSymbolsWereDeleted() : void
    {
        $this->classBasedComparatorWillBeCalled();
        $this->methodBasedComparatorWillNotBeCalled();
        $this->constantBasedComparatorWillNotBeCalled();
        $this->interfaceBasedComparatorWillNotBeCalled();

        self::assertEqualsIgnoringOrder(
            Changes::fromArray([
                Change::changed('class change', true),
            ]),
            $this->comparator->compare(
                self::$stringReflectorFactory->__invoke(
                    <<<'PHP'
<?php

class A {
    const A_CONSTANT = 'foo';
    public $aProperty;
    public function aMethod() {}
}
PHP
                ),
                self::$stringReflectorFactory->__invoke(
                    <<<'PHP'
<?php

class A {}
PHP
                )
            )
        );
    }

    public function testWillRunInterfaceComparators() : void
    {
        $this->classBasedComparatorWillBeCalled();
        $this->methodBasedComparatorWillNotBeCalled();
        $this->constantBasedComparatorWillNotBeCalled();
        $this->interfaceBasedComparatorWillBeCalled();

        self::assertEqualsIgnoringOrder(
            Changes::fromArray([
                Change::changed('class change', true),
                Change::changed('interface change', true),
            ]),
            $this->comparator->compare(
                self::$stringReflectorFactory->__invoke('<?php interface A {}'),
                self::$stringReflectorFactory->__invoke('<?php interface A {}')
            )
        );
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
        $this->methodBasedComparatorWillNotBeCalled();

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

    private function classBasedComparatorWillBeCalled() : void
    {
        $this
            ->classBasedComparison
            ->expects(self::atLeastOnce())
            ->method('compare')
            ->willReturn(Changes::fromArray([
                Change::changed('class change', true),
            ]));
    }

    private function classBasedComparatorWillNotBeCalled() : void
    {
        $this
            ->classBasedComparison
            ->expects(self::never())
            ->method('compare');
    }

    private function methodBasedComparatorWillBeCalled() : void
    {
        $this
            ->methodBasedComparison
            ->expects(self::atLeastOnce())
            ->method('compare')
            ->willReturn(Changes::fromArray([
                Change::changed('method change', true),
            ]));
    }

    private function methodBasedComparatorWillNotBeCalled() : void
    {
        $this
            ->methodBasedComparison
            ->expects(self::never())
            ->method('compare');
    }

    private function constantBasedComparatorWillBeCalled() : void
    {
        $this
            ->constantBasedComparison
            ->expects(self::atLeastOnce())
            ->method('compare')
            ->willReturn(Changes::fromArray([
                Change::changed('constant change', true),
            ]));
    }

    private function constantBasedComparatorWillNotBeCalled() : void
    {
        $this
            ->constantBasedComparison
            ->expects(self::never())
            ->method('compare');
    }

    private function interfaceBasedComparatorWillBeCalled() : void
    {
        $this
            ->interfaceBasedComparison
            ->expects(self::atLeastOnce())
            ->method('compare')
            ->willReturn(Changes::fromArray([
                Change::changed('interface change', true),
            ]));
    }

    private function interfaceBasedComparatorWillNotBeCalled() : void
    {
        $this
            ->interfaceBasedComparison
            ->expects(self::never())
            ->method('compare');
    }
}
