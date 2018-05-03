<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ParameterDefaultValueChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_combine;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ParameterDefaultValueChanged
 */
final class ParameterDefaultValueChangedTest extends TestCase
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
        $changes = (new ParameterDefaultValueChanged())($fromFunction, $toFunction);

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
   function changed($a = 1) {}
   function defaultAdded($a) {}
   function defaultRemoved($a = null) {}
   function defaultTypeChanged($a = '1') {}
   function notChanged($a = 1, $b = 2, $c = 3) {}
   function namesChanged($a = 1, $b = 2, $c = 3) {}
   function orderChanged($a = 1, $b = 2, $c = 3) {}
   function positionOfOptionalParameterChanged($a = 2, $b, $c = 1) {}
   class C {
       static function changed1($a = 1) {}
       function changed2($a = 1) {}
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
   function changed($a = 2) {}
   function defaultAdded($a = 1) {}
   function defaultRemoved($a) {}
   function defaultTypeChanged($a = 1) {}
   function notChanged($a = 1, $b = 2, $c = 3) {}
   function namesChanged($d = 1, $e = 2, $f = 3) {}
   function orderChanged($c = 3, $b = 2, $a = 1) {}
   function positionOfOptionalParameterChanged($a, $b = 2, $c = 1) {}
   class C {
       static function changed1($a = 2) {}
       function changed2($a = 2) {}
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
            'changed'            => [
                '[BC] CHANGED: Default parameter value for parameter $a of changed() changed from 1 to 2',
            ],
            'defaultAdded'       => [],
            'defaultRemoved'     => [],
            'defaultTypeChanged' => [
                '[BC] CHANGED: Default parameter value for parameter $a of defaultTypeChanged() changed from \'1\' to 1',
            ],
            'notChanged'         => [],
            'namesChanged'       => [],
            'orderChanged'       => [
                '[BC] CHANGED: Default parameter value for parameter $a of orderChanged() changed from 1 to 3',
                '[BC] CHANGED: Default parameter value for parameter $c of orderChanged() changed from 3 to 1',
            ],
            'positionOfOptionalParameterChanged' => [],
        ];

        return array_merge(
            array_combine(
                array_keys($functions),
                array_map(
                    /** @psalm-param list<string> $errorMessages https://github.com/vimeo/psalm/issues/2772 */
                    static function (string $function, array $errorMessages) use ($fromReflector, $toReflector) : array {
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
                'C::changed1' => [
                    $fromClassReflector->reflect('C')->getMethod('changed1'),
                    $toClassReflector->reflect('C')->getMethod('changed1'),
                    [
                        '[BC] CHANGED: Default parameter value for parameter $a of C::changed1() changed from 1 to 2',
                    ],
                ],
                'C#changed2'  => [
                    $fromClassReflector->reflect('C')->getMethod('changed2'),
                    $toClassReflector->reflect('C')->getMethod('changed2'),
                    [
                        '[BC] CHANGED: Default parameter value for parameter $a of C#changed2() changed from 1 to 2',
                    ],
                ],
            ]
        );
    }
}
