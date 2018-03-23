<?php
declare(strict_types=1);

namespace RoaveTest\ApiCompare;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Factory\DirectoryReflectorFactory;

/**
 * @covers \Roave\ApiCompare\Comparator
 */
final class ComparatorTest extends TestCase
{
    /**
     * @var StringReflectorFactory|null
     */
    private static $stringReflectorFactory;

    public static function setUpBeforeClass()
    {
        self::$stringReflectorFactory = new StringReflectorFactory();
    }

    /**
     * @param mixed $expected
     * @param mixed $actual
     */
    private static function assertEqualsIgnoringOrder($expected, $actual): void
    {
        self::assertEquals($expected, $actual, '', 0.0, 10, true);
    }

    public function testCompare(): void
    {
        $reflectorFactory = new DirectoryReflectorFactory();
        self::assertEqualsIgnoringOrder(
            Changes::fromArray([
                Change::removed('Parameter something (position 0) in Thing::__construct has been deleted', true),
                Change::removed('Method methodGone in class Thing has been deleted', true),
                Change::removed('Class ClassGone has been deleted', true),
            ]),
            (new Comparator())->compare(
                $reflectorFactory->__invoke(__DIR__ . '/../asset/api/old'),
                $reflectorFactory->__invoke(__DIR__ . '/../asset/api/new')
            )
        );
    }

    public function testRemovingAPrivateMethodDoesNotCauseBreak(): void
    {
        self::assertEqualsIgnoringOrder(
            Changes::new(),
            (new Comparator())->compare(
                self::$stringReflectorFactory->__invoke('<?php class A { private function foo() {} }'),
                self::$stringReflectorFactory->__invoke('<?php class A { }')
            )
        );
    }

    public function testRenamingParametersDoesNotCauseBcBreak(): void
    {
        self::assertEqualsIgnoringOrder(
            Changes::new(),
            (new Comparator())->compare(
                self::$stringReflectorFactory->__invoke('<?php class A { function foo(int $a, string $b) {} }'),
                self::$stringReflectorFactory->__invoke('<?php class A { function foo(int $b, string $a) {} }')
            )
        );
    }

    public function testMakingAClassFinal(): void
    {
        self::assertEqualsIgnoringOrder(
            Changes::fromArray([
                Change::changed('Class A is now final', true),
            ]),
            (new Comparator())->compare(
                self::$stringReflectorFactory->__invoke('<?php class A { }'),
                self::$stringReflectorFactory->__invoke('<?php final class A { }')
            )
        );
    }
}
