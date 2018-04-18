<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased\ParameterTypeChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased\ParameterTypeChanged
 */
final class ParameterTypeChangedTest extends TestCase
{
    /**
     * @dataProvider functionsToBeTested
     *
     * @param string[] $expectedMessages
     */
    public function testDiffs(
        ReflectionFunctionAbstract $fromFunction,
        ReflectionFunctionAbstract $toFunction,
        array $expectedMessages
    ) : void {
        $changes = (new ParameterTypeChanged())
            ->compare($fromFunction, $toFunction);

        self::assertSame(
            $expectedMessages,
            array_map(function (Change $change) : string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /** @return (string[]|ReflectionFunctionAbstract)[][] */
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
   function untouched(int $a, int $b) {}
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

        $fromClassReflector = new ClassReflector($fromLocator);
        $toClassReflector   = new ClassReflector($toLocator);
        $fromReflector      = new FunctionReflector($fromLocator, $fromClassReflector);
        $toReflector        = new FunctionReflector($toLocator, $toClassReflector);

        $functions = [
            'changed'      => [
                '[BC] CHANGED: The parameter $a of changed() changed from int to float',
                '[BC] CHANGED: The parameter $b of changed() changed from int to float',
            ],
            'untouched'    => [],
            'N1\changed'   => [
                '[BC] CHANGED: The parameter $a of N1\changed() changed from N1\A to N2\A',
                '[BC] CHANGED: The parameter $b of N1\changed() changed from N1\A to N2\A',
            ],
            'N1\untouched' => [],
            'N2\changed'   => [
                '[BC] CHANGED: The parameter $a of N2\changed() changed from N2\A to N3\A',
            ],
            'N2\untouched' => [],
            'N3\changed'   => [
                '[BC] CHANGED: The parameter $a of N3\changed() changed from ?int to int',
                '[BC] CHANGED: The parameter $b of N3\changed() changed from ?int to int',
            ],
            'N3\untouched' => [],
        ];

        return array_merge(
            array_combine(
                array_keys($functions),
                array_map(
                    function (string $function, array $errorMessages) use ($fromReflector, $toReflector) : array {
                        return [
                            $fromReflector->reflect($function),
                            $toReflector->reflect($function),
                            $errorMessages,
                        ];
                    },
                    array_keys($functions),
                    $functions
                )
            ),
            [
                'N4\C::changed1' => [
                    $fromClassReflector->reflect('N4\C')->getMethod('changed1'),
                    $toClassReflector->reflect('N4\C')->getMethod('changed1'),
                    [
                        '[BC] CHANGED: The parameter $a of N4\C::changed1() changed from no type to int',
                        '[BC] CHANGED: The parameter $b of N4\C::changed1() changed from no type to int',

                    ],
                ],
                'N4\C#changed2'  => [
                    $fromClassReflector->reflect('N4\C')->getMethod('changed2'),
                    $toClassReflector->reflect('N4\C')->getMethod('changed2'),
                    [
                        '[BC] CHANGED: The parameter $a of N4\C#changed2() changed from no type to int',
                        '[BC] CHANGED: The parameter $b of N4\C#changed2() changed from no type to int',
                    ]
                ],
            ]
        );
    }
}
