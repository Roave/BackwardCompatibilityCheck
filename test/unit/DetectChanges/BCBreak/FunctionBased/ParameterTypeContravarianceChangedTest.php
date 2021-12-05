<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ParameterTypeContravarianceChanged;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsContravariant;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_combine;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ParameterTypeContravarianceChanged
 */
final class ParameterTypeContravarianceChangedTest extends TestCase
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
        $changes = (new ParameterTypeContravarianceChanged(new TypeIsContravariant()))($fromFunction, $toFunction);

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
   function changed(int $a, int $b) {}
   function untouched(int $a, int $b) {}
}

namespace N1 {
   class A {}
   function changed(A $a, A $b) {}
   function untouched(A $a, A $b) {}
}

namespace N2 {
   class A {}
   function changed(A $a, A $b) {}
   function untouched(A $a, A $b) {}
}

namespace N3 {
   function changed(?int $a, ?int $b) {}
   function untouched(?int $a, ?int $b) {}
}

namespace N4 {
   class C {
       static function changed1($a, $b) {}
       function changed2($a, $b) {}
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
   function changed(float $a, float $b) {}
   function untouched($a, $b) {}
}

namespace N1 {
   class A {}
   function changed(\N2\A $a, \N2\A $b) {}
   function untouched(A $a, A $b) {}
}

namespace N2 {
   class A {}
   function changed(\N3\A $b) {}
   function untouched(A $a, A $b) {}
}

namespace N3 {
   class A {}
   function changed(int $d, int $e, int $f) {}
   function untouched(?int $a, ?int $b) {}
}

namespace N4 {
   class C {
       static function changed1(int $a, int $b) {}
       function changed2(int $a, int $b) {}
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
                '[BC] CHANGED: The parameter $a of changed() changed from int to a non-contravariant float',
                '[BC] CHANGED: The parameter $b of changed() changed from int to a non-contravariant float',
            ],
            'untouched'    => [],
            'N1\changed'   => [
                '[BC] CHANGED: The parameter $a of N1\changed() changed from N1\A to a non-contravariant N2\A',
                '[BC] CHANGED: The parameter $b of N1\changed() changed from N1\A to a non-contravariant N2\A',
            ],
            'N1\untouched' => [],
            'N2\changed'   => [
                '[BC] CHANGED: The parameter $a of N2\changed() changed from N2\A to a non-contravariant N3\A',
            ],
            'N2\untouched' => [],
            'N3\changed'   => [
                '[BC] CHANGED: The parameter $a of N3\changed() changed from ?int to a non-contravariant int',
                '[BC] CHANGED: The parameter $b of N3\changed() changed from ?int to a non-contravariant int',
            ],
            'N3\untouched' => [],
        ];

        return array_merge(
            array_combine(
                array_keys($functions),
                array_map(
                    /** @psalm-param list<string> $errorMessages https://github.com/vimeo/psalm/issues/2772 */
                    function (string $function, array $errorMessages) use ($fromReflector, $toReflector) : array {
                        return [
                            $fromReflector->reflectFunction($function),
                            $toReflector->reflectFunction($function),
                            $errorMessages,
                        ];
                    },
                    array_keys($functions),
                    $functions
                )
            ),
            [
                'N4\C::changed1' => [
                    $fromReflector->reflectClass('N4\C')->getMethod('changed1'),
                    $toReflector->reflectClass('N4\C')->getMethod('changed1'),
                    [
                        '[BC] CHANGED: The parameter $a of N4\C::changed1() changed from no type to a non-contravariant int',
                        '[BC] CHANGED: The parameter $b of N4\C::changed1() changed from no type to a non-contravariant int',

                    ],
                ],
                'N4\C#changed2'  => [
                    $fromReflector->reflectClass('N4\C')->getMethod('changed2'),
                    $toReflector->reflectClass('N4\C')->getMethod('changed2'),
                    [
                        '[BC] CHANGED: The parameter $a of N4\C#changed2() changed from no type to a non-contravariant int',
                        '[BC] CHANGED: The parameter $b of N4\C#changed2() changed from no type to a non-contravariant int',
                    ]
                ],
            ]
        );
    }
}
