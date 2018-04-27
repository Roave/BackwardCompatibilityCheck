<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator;
use Roave\ApiCompare\DetectChanges\BCBreak\ClassBased\ClassBased;
use Roave\ApiCompare\DetectChanges\BCBreak\InterfaceBased\InterfaceBased;
use Roave\ApiCompare\DetectChanges\BCBreak\TraitBased\TraitBased;

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

    /** @var TraitBased|MockObject */
    private $traitBasedComparison;

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
        $this->traitBasedComparison     = $this->createMock(TraitBased::class);
        $this->comparator               = new Comparator(
            $this->classBasedComparison,
            $this->interfaceBasedComparison,
            $this->traitBasedComparison
        );
    }

    public function testWillRunSubComparators() : void
    {
        $this->classBasedComparatorWillBeCalled();
        $this->interfaceBasedComparatorWillNotBeCalled();
        $this->traitBasedComparatorWillNotBeCalled();

        self::assertEqualsIgnoringOrder(
            Changes::fromArray([
                Change::changed('class change', true),
            ]),
            $this->comparator->compare(
                self::$stringReflectorFactory->__invoke('<?php class A {}'),
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
        $this->interfaceBasedComparatorWillNotBeCalled();
        $this->traitBasedComparatorWillNotBeCalled();

        self::assertEqualsIgnoringOrder(
            Changes::fromArray([
                Change::changed('class change', true),
            ]),
            $this->comparator->compare(
                self::$stringReflectorFactory->__invoke('<?php class A {}'),
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
        $this->classBasedComparatorWillNotBeCalled();
        $this->interfaceBasedComparatorWillBeCalled();
        $this->traitBasedComparatorWillNotBeCalled();

        self::assertEqualsIgnoringOrder(
            Changes::fromArray([
                Change::changed('interface change', true),
            ]),
            $this->comparator->compare(
                self::$stringReflectorFactory->__invoke('<?php interface A {}'),
                self::$stringReflectorFactory->__invoke('<?php interface A {}'),
                self::$stringReflectorFactory->__invoke('<?php interface A {}')
            )
        );
    }

    public function testWillRunTraitComparators() : void
    {
        $this->classBasedComparatorWillNotBeCalled();
        $this->interfaceBasedComparatorWillNotBeCalled();
        $this->traitBasedComparatorWillBeCalled();

        self::assertEqualsIgnoringOrder(
            Changes::fromArray([
                Change::changed('trait change', true),
            ]),
            $this->comparator->compare(
                self::$stringReflectorFactory->__invoke('<?php trait A {}'),
                self::$stringReflectorFactory->__invoke('<?php trait A {}'),
                self::$stringReflectorFactory->__invoke('<?php trait A {}')
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

    public function testSkipsReflectingUndefinedApi() : void
    {
        $this->classBasedComparatorWillNotBeCalled();

        self::assertEqualsIgnoringOrder(
            Changes::new(),
            $this->comparator->compare(
                self::$stringReflectorFactory->__invoke('<?php '),
                self::$stringReflectorFactory->__invoke('<?php class A { private function foo() {} }'),
                self::$stringReflectorFactory->__invoke('<?php ')
            )
        );
    }

    public function testRemovingAClassCausesABreak() : void
    {
        $this->classBasedComparatorWillNotBeCalled();
        $this->interfaceBasedComparatorWillNotBeCalled();
        $this->traitBasedComparatorWillNotBeCalled();

        self::assertEqualsIgnoringOrder(
            Changes::fromArray([
                Change::removed('Class A has been deleted', true),
            ]),
            $this->comparator->compare(
                self::$stringReflectorFactory->__invoke('<?php class A { private function foo() {} }'),
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
            ->method('__invoke')
            ->willReturn(Changes::fromArray([
                Change::changed('class change', true),
            ]));
    }

    private function classBasedComparatorWillNotBeCalled() : void
    {
        $this
            ->classBasedComparison
            ->expects(self::never())
            ->method('__invoke');
    }

    private function interfaceBasedComparatorWillBeCalled() : void
    {
        $this
            ->interfaceBasedComparison
            ->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturn(Changes::fromArray([
                Change::changed('interface change', true),
            ]));
    }

    private function interfaceBasedComparatorWillNotBeCalled() : void
    {
        $this
            ->interfaceBasedComparison
            ->expects(self::never())
            ->method('__invoke');
    }

    private function traitBasedComparatorWillBeCalled() : void
    {
        $this
            ->traitBasedComparison
            ->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturn(Changes::fromArray([
                Change::changed('trait change', true),
            ]));
    }

    private function traitBasedComparatorWillNotBeCalled() : void
    {
        $this
            ->traitBasedComparison
            ->expects(self::never())
            ->method('__invoke');
    }
}
