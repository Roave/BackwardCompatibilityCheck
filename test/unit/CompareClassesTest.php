<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\CompareClasses;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ClassBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\InterfaceBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased\TraitBased;

/**
 * @covers \Roave\BackwardCompatibility\CompareClasses
 */
final class CompareClassesTest extends TestCase
{
    private static StringReflectorFactory $stringReflectorFactory;

    /** @var ClassBased&MockObject */
    private ClassBased $classBasedComparison;

    /** @var InterfaceBased&MockObject */
    private InterfaceBased $interfaceBasedComparison;

    /** @var TraitBased&MockObject */
    private TraitBased $traitBasedComparison;

    private CompareClasses $compareClasses;

    public static function setUpBeforeClass(): void
    {
        self::$stringReflectorFactory = new StringReflectorFactory();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->classBasedComparison     = $this->createMock(ClassBased::class);
        $this->interfaceBasedComparison = $this->createMock(InterfaceBased::class);
        $this->traitBasedComparison     = $this->createMock(TraitBased::class);
        $this->compareClasses           = new CompareClasses(
            $this->classBasedComparison,
            $this->interfaceBasedComparison,
            $this->traitBasedComparison
        );
    }

    public function testWillRunSubComparators(): void
    {
        $this->classBasedComparatorWillBeCalled();
        $this->interfaceBasedComparatorWillNotBeCalled();
        $this->traitBasedComparatorWillNotBeCalled();

        Assertion::assertChangesEqual(
            Changes::fromList(Change::changed('class change', true)),
            $this->compareClasses->__invoke(
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

    public function testWillNotRunSubComparatorsIfSymbolsWereDeleted(): void
    {
        $this->classBasedComparatorWillBeCalled();
        $this->interfaceBasedComparatorWillNotBeCalled();
        $this->traitBasedComparatorWillNotBeCalled();

        Assertion::assertChangesEqual(
            Changes::fromList(Change::changed('class change', true)),
            $this->compareClasses->__invoke(
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

    public function testWillRunInterfaceComparators(): void
    {
        $this->classBasedComparatorWillNotBeCalled();
        $this->interfaceBasedComparatorWillBeCalled();
        $this->traitBasedComparatorWillNotBeCalled();

        Assertion::assertChangesEqual(
            Changes::fromList(Change::changed('interface change', true)),
            $this->compareClasses->__invoke(
                self::$stringReflectorFactory->__invoke('<?php interface A {}'),
                self::$stringReflectorFactory->__invoke('<?php interface A {}'),
                self::$stringReflectorFactory->__invoke('<?php interface A {}')
            )
        );
    }

    public function testWillRunTraitComparators(): void
    {
        $this->classBasedComparatorWillNotBeCalled();
        $this->interfaceBasedComparatorWillNotBeCalled();
        $this->traitBasedComparatorWillBeCalled();

        Assertion::assertChangesEqual(
            Changes::fromList(Change::changed('trait change', true)),
            $this->compareClasses->__invoke(
                self::$stringReflectorFactory->__invoke('<?php trait A {}'),
                self::$stringReflectorFactory->__invoke('<?php trait A {}'),
                self::$stringReflectorFactory->__invoke('<?php trait A {}')
            )
        );
    }

    public function testAnonymousClassesAreFilteredOut(): void
    {
        $this->classBasedComparatorWillNotBeCalled();
        $this->interfaceBasedComparatorWillNotBeCalled();
        $this->traitBasedComparatorWillNotBeCalled();

        Assertion::assertChangesEqual(
            Changes::empty(),
            $this->compareClasses->__invoke(
                self::$stringReflectorFactory->__invoke('<?php $x = new class () {};'),
                self::$stringReflectorFactory->__invoke('<?php $x = new class () {};'),
                self::$stringReflectorFactory->__invoke('<?php $x = new class () {};')
            )
        );
    }

    public function testSkipsReflectingUndefinedApi(): void
    {
        $this->classBasedComparatorWillNotBeCalled();

        Assertion::assertChangesEqual(
            Changes::empty(),
            $this->compareClasses->__invoke(
                self::$stringReflectorFactory->__invoke('<?php '),
                self::$stringReflectorFactory->__invoke('<?php class A { private function foo() {} }'),
                self::$stringReflectorFactory->__invoke('<?php ')
            )
        );
    }

    public function testSkipsReflectingInternalClassAlikeSymbols(): void
    {
        $this->classBasedComparatorWillNotBeCalled();
        $this->interfaceBasedComparatorWillNotBeCalled();
        $this->traitBasedComparatorWillNotBeCalled();

        Assertion::assertChangesEqual(
            Changes::empty(),
            $this->compareClasses->__invoke(
                self::$stringReflectorFactory->__invoke(<<<'PHP'
<?php

/** @internal */
class A {}
/** @internal */
interface B {}
/** @internal */
trait C {}
PHP
                ),
                self::$stringReflectorFactory->__invoke('<?php '),
                self::$stringReflectorFactory->__invoke('<?php ')
            )
        );
    }

    public function testRemovingAClassCausesABreak(): void
    {
        $this->classBasedComparatorWillNotBeCalled();
        $this->interfaceBasedComparatorWillNotBeCalled();
        $this->traitBasedComparatorWillNotBeCalled();

        Assertion::assertChangesEqual(
            Changes::fromList(Change::removed('Class A has been deleted', true)),
            $this->compareClasses->__invoke(
                self::$stringReflectorFactory->__invoke('<?php class A { private function foo() {} }'),
                self::$stringReflectorFactory->__invoke('<?php class A { private function foo() {} }'),
                self::$stringReflectorFactory->__invoke('<?php ')
            )
        );
    }

    private function classBasedComparatorWillBeCalled(): void
    {
        $this
            ->classBasedComparison
            ->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturn(Changes::fromList(Change::changed('class change', true)));
    }

    private function classBasedComparatorWillNotBeCalled(): void
    {
        $this
            ->classBasedComparison
            ->expects(self::never())
            ->method('__invoke');
    }

    private function interfaceBasedComparatorWillBeCalled(): void
    {
        $this
            ->interfaceBasedComparison
            ->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturn(Changes::fromList(Change::changed('interface change', true)));
    }

    private function interfaceBasedComparatorWillNotBeCalled(): void
    {
        $this
            ->interfaceBasedComparison
            ->expects(self::never())
            ->method('__invoke');
    }

    private function traitBasedComparatorWillBeCalled(): void
    {
        $this
            ->traitBasedComparison
            ->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturn(Changes::fromList(Change::changed('trait change', true)));
    }

    private function traitBasedComparatorWillNotBeCalled(): void
    {
        $this
            ->traitBasedComparison
            ->expects(self::never())
            ->method('__invoke');
    }
}
