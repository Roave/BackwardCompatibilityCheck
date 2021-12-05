<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ReturnTypeCovarianceChanged;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsCovariant;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_combine;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ReturnTypeCovarianceChanged
 */
final class ReturnTypeCovarianceChangedTest extends TestCase
{
    /**
     * @dataProvider functionsToBeTested
     *
     * @param string[] $expectedMessages
     */
    public function testDiffs(
        ReflectionMethod|ReflectionFunction $fromFunction,
        ReflectionMethod|ReflectionFunction $toFunction,
        array $expectedMessages
    ) : void {
        $changes = (new ReturnTypeCovarianceChanged(new TypeIsCovariant()))($fromFunction, $toFunction);

        self::assertSame(
            $expectedMessages,
            array_map(function (Change $change) : string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /**
     * @return array<string, array{
     *     0: ReflectionMethod|ReflectionFunction,
     *     1: ReflectionMethod|ReflectionFunction,
     *     2: list<string>
     * }>
     */
    public function functionsToBeTested() : array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

namespace {
   class A {}
   function changed() : A {}
   function untouched() : A {}
}

namespace N1 {
   class B {}
   function changed() : int {}
   function untouched() : int {}
}

namespace N2 {
   function changed() : int {}
   function untouched() {}
}

namespace N3 {
   function changed() : int {}
   function untouched() : int {}
}

namespace N4 {
   class C {
       static function changed1() : int {}
       function changed2() : int {}
   }
}
PHP
            ,
            $astLocator
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

namespace {
   class A {}
   function changed() : \N1\B {}
   function untouched() : A {}
}

namespace N1 {
   class B {}
   function changed() : float {}
   function untouched() : int {}
}

namespace N2 {
   function changed() : ?int {}
   function untouched() {}
}

namespace N3 {
   function changed() {}
   function untouched() : int {}
}

namespace N4 {
   class C {
       static function changed1() {}
       function changed2() {}
   }
}
PHP
            ,
            $astLocator
        );

        $fromReflector      = new DefaultReflector($fromLocator);
        $toReflector        = new DefaultReflector($toLocator);

        $functions = [
            'changed'      => [
                '[BC] CHANGED: The return type of changed() changed from A to the non-covariant N1\B',
            ],
            'untouched'    => [],
            'N1\changed'   => [
                '[BC] CHANGED: The return type of N1\changed() changed from int to the non-covariant float',
            ],
            'N1\untouched' => [],
            'N2\changed'   => [
                '[BC] CHANGED: The return type of N2\changed() changed from int to the non-covariant ?int',
            ],
            'N2\untouched' => [],
            'N3\changed'   => [
                '[BC] CHANGED: The return type of N3\changed() changed from int to the non-covariant no type',
            ],
            'N3\untouched' => [],
        ];

        return array_merge(
            array_combine(
                array_keys($functions),
                array_map(
                    static fn (string $function, array $errors): array => [
                        $fromReflector->reflectFunction($function),
                        $toReflector->reflectFunction($function),
                        $errors,
                    ],
                    array_keys($functions),
                    $functions
                )
            ),
            [
                'N4\C::changed1' => [
                    $fromReflector->reflectClass('N4\C')->getMethod('changed1'),
                    $toReflector->reflectClass('N4\C')->getMethod('changed1'),
                    ['[BC] CHANGED: The return type of N4\C::changed1() changed from int to the non-covariant no type'],
                ],
                'N4\C#changed2'  => [
                    $fromReflector->reflectClass('N4\C')->getMethod('changed2'),
                    $toReflector->reflectClass('N4\C')->getMethod('changed2'),
                    ['[BC] CHANGED: The return type of N4\C#changed2() changed from int to the non-covariant no type'],
                ],
            ]
        );
    }
}
