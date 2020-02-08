<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ReturnTypeChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_combine;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ReturnTypeChanged
 */
final class ReturnTypeChangedTest extends TestCase
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
        $changes = (new ReturnTypeChanged())
            ->__invoke($fromFunction, $toFunction);

        self::assertSame(
            $expectedMessages,
            array_map(function (Change $change) : string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /**
     * @return array<string, array<int, ReflectionFunctionAbstract|array<int, string>>>
     *
     * @psalm-return array<string, array{0: ReflectionFunctionAbstract, 1: ReflectionFunctionAbstract, 2: list<string>}>
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

        $fromClassReflector = new ClassReflector($fromLocator);
        $toClassReflector   = new ClassReflector($toLocator);
        $fromReflector      = new FunctionReflector($fromLocator, $fromClassReflector);
        $toReflector        = new FunctionReflector($toLocator, $toClassReflector);

        $functions = [
            'changed'      => [
                '[BC] CHANGED: The return type of changed() changed from A to N1\B',
            ],
            'untouched'    => [],
            'N1\changed'   => [
                '[BC] CHANGED: The return type of N1\changed() changed from int to float',
            ],
            'N1\untouched' => [],
            'N2\changed'   => [
                '[BC] CHANGED: The return type of N2\changed() changed from int to ?int',
            ],
            'N2\untouched' => [],
            'N3\changed'   => [
                '[BC] CHANGED: The return type of N3\changed() changed from int to no type',
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
                    ['[BC] CHANGED: The return type of N4\C::changed1() changed from int to no type'],
                ],
                'N4\C#changed2'  => [
                    $fromClassReflector->reflect('N4\C')->getMethod('changed2'),
                    $toClassReflector->reflect('N4\C')->getMethod('changed2'),
                    ['[BC] CHANGED: The return type of N4\C#changed2() changed from int to no type'],
                ],
            ]
        );
    }
}
