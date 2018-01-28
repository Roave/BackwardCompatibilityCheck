<?php
declare(strict_types=1);

namespace RoaveTest\ApiCompare;

use Roave\ApiCompare\Comparator;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Factory\DirectoryReflectorFactory;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\ApiCompare\Comparator
 */
final class ComparatorTest extends TestCase
{
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
        $reflectorFactory = function (string $sourceCode): ClassReflector {
            $astLocator = (new BetterReflection())->astLocator();
            return new ClassReflector(
                new AggregateSourceLocator([
                    new PhpInternalSourceLocator($astLocator),
                    new EvaledCodeSourceLocator($astLocator),
                    new StringSourceLocator($sourceCode, $astLocator),
                ])
            );
        };

        self::assertEqualsIgnoringOrder(
            [],
            (new Comparator())->compare(
                $reflectorFactory('<?php class A { function foo(int $a, string $b) {} }'),
                $reflectorFactory('<?php class A { function foo(int $b, string $a) {} }')
            )
        );
    }
}
