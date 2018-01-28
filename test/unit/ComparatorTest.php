<?php
declare(strict_types=1);

namespace RoaveTest\ApiCompare;

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
            [
                '[BC] Parameter something (position 0) in Thing::__construct has been deleted',
                '[BC] Method methodGone in class Thing has been deleted',
                '[BC] Class ClassGone has been deleted',
            ],
            (new Comparator())->compare(
                $reflectorFactory->__invoke(__DIR__ . '/../asset/api/old'),
                $reflectorFactory->__invoke(__DIR__ . '/../asset/api/new')
            )
        );
    }

    public function testRenamingParametersDoesNotCauseBcBreak(): void
    {
        self::assertEqualsIgnoringOrder(
            [],
            (new Comparator())->compare(
                self::$stringReflectorFactory->__invoke('<?php class A { function foo(int $a, string $b) {} }'),
                self::$stringReflectorFactory->__invoke('<?php class A { function foo(int $b, string $a) {} }')
            )
        );
    }
}
