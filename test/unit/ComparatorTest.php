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
    public function testCompare(): void
    {
        $reflectorFactory = new DirectoryReflectorFactory();
        self::assertEquals(
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

    public function testRenamingParametersDoesNotCauseBCBreak()
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

        self::assertEquals(
            [],
            (new Comparator())->compare(
                $reflectorFactory('<?php class A { function foo(int $a, string $b) {} }'),
                $reflectorFactory('<?php class A { function foo(int $b, string $a) {} }')
            )
        );
    }
}
