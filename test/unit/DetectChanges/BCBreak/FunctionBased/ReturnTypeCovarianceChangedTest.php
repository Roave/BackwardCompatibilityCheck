<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\DetectChanges\BCBreak\FunctionBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\DetectChanges\BCBreak\FunctionBased\ReturnTypeCovarianceChanged;
use Roave\ApiCompare\DetectChanges\Variance\TypeIsCovariant;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\ApiCompare\DetectChanges\BCBreak\FunctionBased\ReturnTypeCovarianceChanged
 */
final class ReturnTypeCovarianceChangedTest extends TestCase
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
        $changes = (new ReturnTypeCovarianceChanged(new TypeIsCovariant()))
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

        $fromClassReflector = new ClassReflector($fromLocator);
        $toClassReflector   = new ClassReflector($toLocator);
        $fromReflector      = new FunctionReflector($fromLocator, $fromClassReflector);
        $toReflector        = new FunctionReflector($toLocator, $toClassReflector);

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
                    ['[BC] CHANGED: The return type of N4\C::changed1() changed from int to the non-covariant no type'],
                ],
                'N4\C#changed2'  => [
                    $fromClassReflector->reflect('N4\C')->getMethod('changed2'),
                    $toClassReflector->reflect('N4\C')->getMethod('changed2'),
                    ['[BC] CHANGED: The return type of N4\C#changed2() changed from int to the non-covariant no type'],
                ],
            ]
        );
    }
}
